<?php

namespace Codexalta\Vaultix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Codexalta\Vaultix\Models\BackupJob;

class VaultixBackupCommand extends Command
{
    protected $signature = 'vaultix:run {--job= : ID of a specific job to run immediately}';
    protected $description = 'Run scheduled dynamic backups managed by Vaultix';

    protected $isManualTrigger = false;

    public function handle()
    {
        // Apply user-defined timezone
        $tz = \Codexalta\Vaultix\Models\VaultixSetting::get('timezone', config('app.timezone'));
        date_default_timezone_set($tz);

        $specificJobId = $this->option('job');
        $currentTime = \Carbon\Carbon::now()->toDateTimeString();
        
        if ($specificJobId) {
            $this->isManualTrigger = true;
            $jobs = BackupJob::where('id', $specificJobId)->get();
            Log::info("Vaultix: Manual trigger for Job ID {$specificJobId} at {$currentTime}");
        } else {
            $allEnabled = BackupJob::where('is_enabled', true)->count();
            $jobs = BackupJob::where('is_enabled', true)
                ->where(function ($query) {
                    $query->whereNull('next_run_at')->orWhere('next_run_at', '<=', \Carbon\Carbon::now());
                })->get();
            
            Log::info("Vaultix Scheduler Check: Time is {$currentTime}. Found {$allEnabled} enabled jobs, {$jobs->count()} are due for run.");
        }

        if ($jobs->isEmpty()) return;
        foreach ($jobs as $job) {
            $this->processJob($job);
        }
        Cache::put('vaultix_scheduler_heartbeat', now()->toDateTimeString(), now()->addDays(2));
    }

    protected function processJob($job)
    {
        $this->info("Processing Job: {$job->name}");
        $dashboardUrl = route('vaultix.index');

        // 1. Safety Check (Space)
        try {
            $path = config('vaultix.monitor_path', base_path());
            $freeBytes = disk_free_space($path);
            
            $dbName = config('database.connections.' . config('database.default') . '.database');
            $dbSize = 0;
            try {
                $dbResult = \Illuminate\Support\Facades\DB::select("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ?", [$dbName]);
                $dbSize = (int) ($dbResult[0]->size ?? 0);
            } catch (\Exception $e) {}

            $fileSize = 0;
            if (function_exists('exec') && PHP_OS_FAMILY !== 'Windows') {
                $output = exec("du -sb " . escapeshellarg(base_path()) . " --exclude='vendor' --exclude='node_modules' --exclude='.git'");
                if ($output) $fileSize = (int) explode("\t", $output)[0];
            }

            $requiredSpace = ($dbSize + $fileSize) * 1.5;
            if ($freeBytes < $requiredSpace) {
                $msg = "Insufficient storage for backup '{$job->name}'. Free: " . round($freeBytes/1024/1024, 1) . "MB, Required: " . round($requiredSpace/1024/1024, 1) . "MB";
                Log::critical("Vaultix Safety Abort: " . $msg);
                
                if ($job->notify_on_failure && $job->notification_email) {
                    Mail::send('vaultix::emails.notification', [
                        'status' => 'failed',
                        'job' => $job,
                        'size' => null,
                        'error' => $msg,
                        'dashboardUrl' => $dashboardUrl,
                        'messageText' => $this->isManualTrigger ? 'The manual backup you triggered has failed due to storage issues.' : 'The scheduled backup job for your project has failed.'
                    ], function($m) use ($job) {
                        $m->to($job->notification_email)->subject("🚨 Vaultix Safety Alert: Storage Insufficient");
                    });
                }
                return;
            }
        } catch (\Exception $e) { Log::warning("Vaultix Pre-check: " . $e->getMessage()); }

        // 2. Dynamic Configuration
        $dest = $job->destination;
        $diskConfig = $this->getDiskConfig($dest);
        
        Log::info("Vaultix: Starting backup job '{$job->name}' for destination '{$dest->name}'");

        \Illuminate\Support\Facades\Config::set('filesystems.disks.vaultix_disk', $diskConfig);
        
        // CRITICAL FIX: Laravel caches resolved disks in the FilesystemManager instance (Queue Worker memory).
        // Since we reuse the "vaultix_disk" name, we MUST tell Laravel to forget the cached instance,
        // otherwise subsequent jobs in the same queue worker will upload to the FIRST job's destination!
        \Illuminate\Support\Facades\Storage::forgetDisk('vaultix_disk');
        
        $folderName = str_replace(' ', '', ucwords(preg_replace('/[^A-Za-z0-9 ]/', '', $job->custom_folder_name ?: config('app.name'))));
        \Illuminate\Support\Facades\Config::set('backup.backup.name', $folderName);
        \Illuminate\Support\Facades\Config::set('backup.backup.destination.disks', ['vaultix_disk']);
        
        $validatorEmail = $job->notification_email ?? config('mail.from.address', 'backup@domain.com');
        \Illuminate\Support\Facades\Config::set('backup.notifications.mail.to', $validatorEmail);

        // Disable Spatie's default notifications to prevent duplicates
        \Illuminate\Support\Facades\Config::set('backup.notifications.notifications', [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [],
        ]);

        Log::info("Vaultix: Config set for '{$folderName}'. Ensuring directory exists...");

        // 3. Execution
        try {
            // Ensure destination folder exists to prevent "UnableToReadFile" on some adapters during reachability check
            try {
                \Illuminate\Support\Facades\Storage::disk('vaultix_disk')->makeDirectory($folderName);
            } catch (\Exception $e) {
                Log::warning("Vaultix: Could not pre-verify folder '{$folderName}' (might be normal for some providers): " . $e->getMessage());
            }

            \Codexalta\Vaultix\Models\VaultixActivity::log(!$this->isManualTrigger ? 'auto_run' : 'manual_run', 'Job', $job->name, "Backup process started.");
            
            // We pass --config=backup to force Spatie Backup to re-read the configuration array
            // after we've modified it via Config::set() above. This fixes issues with cached Config objects.
            $params = [
                '--only-to-disk' => 'vaultix_disk', 
                '--no-interaction' => true,
                '--disable-notifications' => true,
                '--config' => 'backup'
            ];
            if ($job->type === 'db_only') $params['--only-db'] = true;
            if ($job->type === 'files_only') $params['--only-files'] = true;

            $exitCode = Artisan::call('backup:run', $params);
            $output = Artisan::output();
            
            Log::info("Vaultix: backup:run finished with code {$exitCode}. Output: " . substr($output, 0, 500));

            if ($exitCode === 0) {
                \Codexalta\Vaultix\Models\VaultixActivity::log(!$this->isManualTrigger ? 'auto_finish' : 'manual_finish', 'Job', $job->name, "Backup completed successfully.");
                $files = \Illuminate\Support\Facades\Storage::disk('vaultix_disk')->files($folderName);
                Log::info("Vaultix: Files found in storage: " . count($files));
                
                $latestFile = collect($files)->sortByDesc(fn($f) => \Illuminate\Support\Facades\Storage::disk('vaultix_disk')->lastModified($f))->first();
                $size = $latestFile ? \Illuminate\Support\Facades\Storage::disk('vaultix_disk')->size($latestFile) : 0;

                \Codexalta\Vaultix\Models\Backup::create([
                    'job_id' => $job->id, 'destination_id' => $dest->id, 'file_path' => $latestFile,
                    'file_name' => basename($latestFile), 'file_size' => $size, 'status' => 'success', 'completed_at' => now(),
                ]);

                if ($job->notify_on_success && $job->notification_email) {
                    Log::info("Vaultix: Sending success email to {$job->notification_email}");
                    $formattedSize = round($size/1024/1024, 2) . " MB";
                    Mail::send('vaultix::emails.notification', [
                        'status' => 'success',
                        'job' => $job,
                        'size' => $formattedSize,
                        'error' => null,
                        'dashboardUrl' => $dashboardUrl,
                        'messageText' => $this->isManualTrigger ? 'The manual backup you triggered has been completed successfully.' : 'The scheduled backup job for your project has been completed successfully.'
                    ], function($m) use ($job) {
                        $m->to($job->notification_email)->subject("✅ Vaultix Success: {$job->name}");
                    });
                }
            } else {
                throw new \Exception("Backup failed with exit code: {$exitCode}. Output: {$output}");
            }

        } catch (\Exception $e) {
            Log::error("Vaultix Engine Failure for {$job->name}: " . $e->getMessage());
            \Codexalta\Vaultix\Models\Backup::create([
                'job_id' => $job->id, 'destination_id' => $dest->id, 'file_path' => 'failed',
                'file_name' => 'failed', 'status' => 'failed', 'completed_at' => now(),
            ]);

            if ($job->notify_on_failure && $job->notification_email) {
                Mail::send('vaultix::emails.notification', [
                    'status' => 'failed',
                    'job' => $job,
                    'size' => null,
                    'error' => $e->getMessage(),
                    'dashboardUrl' => $dashboardUrl,
                    'messageText' => $this->isManualTrigger ? 'The manual backup you triggered has failed.' : 'The scheduled backup job for your project has failed.'
                ], function($m) use ($job) {
                    $m->to($job->notification_email)->subject("❌ Vaultix Failure: {$job->name}");
                });
            }
        }

        $job->update(['last_run_at' => now(), 'next_run_at' => $this->calculateNextRun($job->frequency, $job)]);
    }

    public function getDiskConfig($dest)
    {
        $creds = $dest->credentials;
        if ($dest->provider === 'gdrive') {
            return ['driver' => 'google', 'clientId' => $creds['client_id'] ?? null, 'clientSecret' => $creds['client_secret'] ?? null, 'refreshToken' => $creds['refresh_token'] ?? null, 'folderId' => $creds['folder_id'] ?? null];
        }

        // Improved S3 Logic: Use path-style ONLY if endpoint is provided (for R2/Minio)
        $config = [
            'driver' => 's3',
            'key' => $creds['key'] ?? ($creds['access_key'] ?? ($creds['r2_key'] ?? null)),
            'secret' => $creds['secret'] ?? ($creds['secret_key'] ?? ($creds['r2_secret'] ?? null)),
            'bucket' => $creds['bucket'] ?? ($creds['r2_bucket'] ?? null),
            'region' => $creds['region'] ?? 'us-east-1',
            'endpoint' => $creds['endpoint'] ?? null,
            'use_path_style_endpoint' => !empty($creds['endpoint']), 
        ];

        if ($dest->provider === 'sftp') {
            return ['driver' => 'sftp', 'host' => $creds['host'] ?? null, 'username' => $creds['username'] ?? null, 'password' => $creds['password'] ?? null, 'port' => 22, 'root' => $creds['root'] ?? '/'];
        }

        return $config;
    }

    protected function calculateNextRun($frequency, $job = null)
    {
        $now = now(); 
        $time = $job->backup_time ?? '00:00'; 
        $day = $job->backup_day ?? 'Monday';
        [$hour, $minute] = explode(':', $time);

        switch ($frequency) {
            case 'hourly': 
                return $now->copy()->addHour()->startOfHour();
            case '6_hours': 
                return $now->copy()->addHours(6);
            case '12_hours': 
                return $now->copy()->addHours(12);
            case 'daily': 
                $next = $now->copy()->setTime($hour, $minute); 
                return $next->isPast() ? $next->addDay() : $next;
            case 'weekly': 
                return $now->copy()->next($day)->setTime($hour, $minute);
            case 'monthly': 
                // Check if day is 'last' or a number
                if ($day === 'last') {
                    $next = $now->copy()->endOfMonth()->setTime($hour, $minute);
                } else {
                    $next = $now->copy()->day((int)$day)->setTime($hour, $minute);
                }
                return $next->isPast() ? $next->addMonth() : $next;
            default: 
                return $now->copy()->addDay();
        }
    }
}

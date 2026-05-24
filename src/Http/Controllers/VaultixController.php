<?php

namespace Codexalta\Vaultix\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Codexalta\Vaultix\Models\BackupDestination;
use Codexalta\Vaultix\Models\BackupJob;
use Codexalta\Vaultix\Models\VaultixSetting;
use Codexalta\Vaultix\Models\VaultixActivity;
class VaultixController extends Controller
{
    public function index(Request $request)
    {
        date_default_timezone_set(VaultixSetting::get('timezone', config('app.timezone')));
        $destinations = BackupDestination::all();
        $jobs = BackupJob::with('destination')->get();

        $schedulerLastRun = Cache::get('vaultix_scheduler_heartbeat');
        
        // Ensure it's not an incomplete class
        if ($schedulerLastRun instanceof \__PHP_Incomplete_Class) {
            $schedulerLastRun = null;
        }

        // If stored as a string (best practice), parse it back to Carbon
        if (is_string($schedulerLastRun)) {
            try {
                $schedulerLastRun = \Illuminate\Support\Carbon::parse($schedulerLastRun);
            } catch (\Exception $e) {
                $schedulerLastRun = null;
            }
        }

        $isSchedulerHealthy = false;
        if ($schedulerLastRun && method_exists($schedulerLastRun, 'diffInMinutes')) {
            try {
                $isSchedulerHealthy = $schedulerLastRun->diffInMinutes(now()) < 65;
            } catch (\Error $e) {
                \Log::warning("Vaultix: Scheduler heartbeat cache issue: " . $e->getMessage());
                $schedulerLastRun = null;
            }
        }

        $isQueueHealthy = false;
        if (function_exists('exec')) {
            exec("ps aux | grep 'queue:work' | grep -v grep", $output);
            $isQueueHealthy = count($output) > 0;
        }

        $projectSize = $this->getProjectSize();
        $diskUsage = $this->getDiskUsage();

        // Filtering & Pagination Logic
        $query = \Codexalta\Vaultix\Models\Backup::with(['job', 'destination'])->latest();

        if ($request->filled('provider')) {
            $query->whereHas('destination', function($q) use ($request) {
                $q->where('provider', $request->provider);
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('completed_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('completed_at', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->has('all') ? 1000 : 20;
        $backups = $query->paginate($perPage)->withQueryString();

        return view('vaultix::index', compact('destinations', 'jobs', 'isSchedulerHealthy', 'isQueueHealthy', 'schedulerLastRun', 'backups', 'diskUsage', 'projectSize'));
    }

    public function createDestination()
    {
        return view('vaultix::destinations.create');
    }

    public function storeDestination(Request $request)
    {
        date_default_timezone_set(VaultixSetting::get('timezone', config('app.timezone')));
        $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:gdrive,s3,r2,sftp',
            'credentials' => 'required|array',
            'backup_type' => 'required|in:db_only,files_only,full',
            'frequency' => 'required|in:daily,weekly,monthly,hourly,6_hours,12_hours',
            'backup_time' => 'required|string|regex:/^[0-9]{2}:[0-9]{2}$/',
            'backup_day' => 'nullable|string',
            'next_run_override' => 'nullable|date',
            'custom_folder_name' => 'nullable|string|max:255',
            'notification_email' => 'nullable|email|max:255',
            'keep_all_backups_for_days' => 'required|integer|min:0',
            'keep_daily_backups_for_days' => 'required|integer|min:0',
            'keep_weekly_backups_for_weeks' => 'required|integer|min:0',
            'keep_monthly_backups_for_months' => 'required|integer|min:0',
        ]);

        $destination = BackupDestination::create($request->only(['name', 'provider', 'credentials']));

        // Create the backup job with user selections
        $job = new BackupJob([
            'destination_id' => $destination->id,
            'name' => $destination->name . " (" . ucfirst($request->frequency) . ")",
            'type' => $request->backup_type,
            'custom_folder_name' => $request->custom_folder_name,
            'notification_email' => $request->notification_email,
            'notify_on_success' => $request->has('notify_on_success') ? 1 : 0,
            'notify_on_failure' => $request->has('notify_on_failure') ? 1 : 0,
            'frequency' => $request->frequency,
            'backup_time' => $request->backup_time,
            'backup_day' => $request->backup_day,
            'keep_all_backups_for_days' => $request->keep_all_backups_for_days,
            'keep_daily_backups_for_days' => $request->keep_daily_backups_for_days,
            'keep_weekly_backups_for_weeks' => $request->keep_weekly_backups_for_weeks,
            'keep_monthly_backups_for_months' => $request->keep_monthly_backups_for_months,
        ]);

        // Calculate next run
        $nextRun = $request->filled('next_run_override')
            ? \Carbon\Carbon::parse($request->next_run_override)
            : $this->calculateNextRunAt($request->frequency, $request->backup_time, $request->backup_day);

        $job->next_run_at = $nextRun;
        $job->save();

        VaultixActivity::log('create', 'Destination', $destination->name, "Created new storage destination and default job.");

        return redirect()->route('vaultix.index')->with('success', 'Storage destination added and default job created!');
    }

    public function editDestination(BackupDestination $destination)
    {
        $job = $destination->jobs()->first();
        return view('vaultix::destinations.edit', compact('destination', 'job'));
    }

    public function updateDestination(Request $request, BackupDestination $destination)
    {
        date_default_timezone_set(VaultixSetting::get('timezone', config('app.timezone')));
        $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:gdrive,s3,r2,sftp',
            'credentials' => 'required|array',
            'backup_type' => 'required|in:db_only,files_only,full',
            'frequency' => 'required|in:daily,weekly,monthly,hourly,6_hours,12_hours',
            'backup_time' => 'required|string|regex:/^[0-9]{2}:[0-9]{2}$/',
            'backup_day' => 'nullable|string',
            'next_run_override' => 'nullable|date',
            'custom_folder_name' => 'nullable|string|max:255',
            'notification_email' => 'nullable|email|max:255',
            'keep_all_backups_for_days' => 'required|integer|min:0',
            'keep_daily_backups_for_days' => 'required|integer|min:0',
            'keep_weekly_backups_for_weeks' => 'required|integer|min:0',
            'keep_monthly_backups_for_months' => 'required|integer|min:0',
        ]);

        // Capture OLD state
        $oldData = [
            'credentials' => $destination->credentials,
            'settings' => $destination->jobs()->first() ? $destination->jobs()->first()->toArray() : null
        ];

        $destination->update($request->only(['name', 'provider', 'credentials']));

        // Update the associated job
        $job = $destination->jobs()->first();
        if ($job) {
            $job->update([
                'name' => $destination->name . " (" . ucfirst($request->frequency) . ")",
                'type' => $request->backup_type,
                'custom_folder_name' => $request->custom_folder_name,
                'notification_email' => $request->notification_email,
                'notify_on_success' => $request->has('notify_on_success') ? 1 : 0,
                'notify_on_failure' => $request->has('notify_on_failure') ? 1 : 0,
                'frequency' => $request->frequency,
                'backup_time' => $request->backup_time,
                'backup_day' => $request->backup_day,
                'keep_all_backups_for_days' => $request->keep_all_backups_for_days,
                'keep_daily_backups_for_days' => $request->keep_daily_backups_for_days,
                'keep_weekly_backups_for_weeks' => $request->keep_weekly_backups_for_weeks,
                'keep_monthly_backups_for_months' => $request->keep_monthly_backups_for_months,
                'next_run_at' => $request->filled('next_run_override')
                    ? \Carbon\Carbon::parse($request->next_run_override)
                    : $this->calculateNextRunAt($request->frequency, $request->backup_time, $request->backup_day),
            ]);
        }

        // Capture NEW state
        $newData = [
            'credentials' => $destination->credentials,
            'settings' => $job ? $job->toArray() : null
        ];

        VaultixActivity::log('update', 'Destination', $destination->name, "Updated backup configuration.", $oldData, $newData);
        return redirect()->route('vaultix.index')->with('success', 'Backup configuration updated successfully!');
    }

    public function destroyDestination(BackupDestination $destination)
    {
        $name = $destination->name;
        $destination->delete();
        VaultixActivity::log('delete', 'Destination', $name, "Deleted destination and associated jobs.");
        return redirect()->route('vaultix.index')->with('success', 'Destination and associated jobs deleted.');
    }

    public function testConnection(BackupDestination $destination)
    {
        try {
            // 1. Setup a temporary disk configuration
            $command = new \Codexalta\Vaultix\Commands\VaultixBackupCommand();
            $diskConfig = $this->getDiskConfigForTesting($destination);

            config(['filesystems.disks.vaultix_test' => $diskConfig]);
            
            // Clear the cached disk instance so Laravel builds a new adapter with the new config
            \Illuminate\Support\Facades\Storage::forgetDisk('vaultix_test');

            // 2. Try to list files or check existence
            $exists = \Illuminate\Support\Facades\Storage::disk('vaultix_test')->allFiles('/');

            return back()->with('success', "Connection successful! Found files in storage.");
        } catch (\Exception $e) {
            return back()->with('error', "Connection failed: " . $e->getMessage());
        }
    }

    protected function getDiskConfigForTesting($dest)
    {
        $creds = $dest->credentials;
        if ($dest->provider === 'gdrive') {
            return [
                'driver' => 'google',
                'clientId' => $creds['client_id'] ?? null,
                'clientSecret' => $creds['client_secret'] ?? null,
                'refreshToken' => $creds['refresh_token'] ?? null,
                'folderId' => $creds['folder_id'] ?? null,
            ];
        }

        if ($dest->provider === 'sftp') {
            return [
                'driver'   => 'sftp',
                'host'     => $creds['host'] ?? null,
                'username' => $creds['username'] ?? null,
                'password' => $creds['password'] ?? null,
                'port'     => (int) ($creds['port'] ?? 22),
                'root'     => $creds['root'] ?? '/',
            ];
        }

        if ($dest->provider === 's3' || $dest->provider === 'r2') {
            return [
                'driver'                  => 's3',
                'key'                     => $creds['key'] ?? ($creds['access_key'] ?? ($creds['r2_key'] ?? null)),
                'secret'                  => $creds['secret'] ?? ($creds['secret_key'] ?? ($creds['r2_secret'] ?? null)),
                'region'                  => $dest->provider === 'r2' ? 'auto' : ($creds['region'] ?? 'us-east-1'),
                'bucket'                  => $creds['bucket'] ?? ($creds['r2_bucket'] ?? null),
                'endpoint'                => $creds['endpoint'] ?? null,
                'use_path_style_endpoint' => $dest->provider === 'r2' || ($creds['use_path_style'] ?? false),
            ];
        }

        return [];
    }

    public function redirectToGoogle(Request $request)
    {
        $clientId = $request->query('client_id');
        $clientSecret = $request->query('client_secret');
        $destinationId = $request->query('destination_id'); // present when editing

        if (!$clientId || !$clientSecret) {
            return back()->with('error', 'Please enter Client ID and Secret first.');
        }

        session(['vaultix_gdrive_creds' => ['id' => $clientId, 'secret' => $clientSecret]]);
        session(['vaultix_gdrive_destination_id' => $destinationId]);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => route('vaultix.auth.google.callback'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/drive.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function handleGoogleCallback(Request $request)
    {
        $code = $request->query('code');
        $creds = session('vaultix_gdrive_creds');

        // Determine where to redirect back (create or edit page)
        $destinationId = session('vaultix_gdrive_destination_id');

        if (!$code || !$creds) {
            $fallbackRoute = $destinationId
                ? route('vaultix.destinations.edit', $destinationId)
                : route('vaultix.destinations.create');
            return redirect($fallbackRoute)->with('error', 'Authorization failed or timed out.');
        }

        try {
            $response = \Illuminate\Support\Facades\Http::post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $creds['id'],
                'client_secret' => $creds['secret'],
                'redirect_uri' => route('vaultix.auth.google.callback'),
                'grant_type' => 'authorization_code',
            ]);

            $data = $response->json();

            if (isset($data['refresh_token'])) {
                // Clear the temporary session data
                session()->forget(['vaultix_gdrive_creds', 'vaultix_gdrive_destination_id']);

                if ($destinationId) {
                    // Redirect back to EDIT page with token pre-filled
                    return redirect()->route('vaultix.destinations.edit', $destinationId)
                        ->with('vaultix_gdrive_token', [
                            'refresh_token' => $data['refresh_token'],
                            'client_id'     => $creds['id'],
                            'client_secret' => $creds['secret'],
                        ])
                        ->with('success', 'Google Drive authorized! Token has been pre-filled below — save to confirm.');
                }

                // Redirect to CREATE page
                return redirect()->route('vaultix.destinations.create', [
                    'refresh_token' => $data['refresh_token'],
                    'client_id'     => $creds['id'],
                    'client_secret' => $creds['secret'],
                    'provider'      => 'gdrive',
                ])->with('success', 'Google Drive authorized! Refresh token generated.');
            }

            $fallbackRoute = $destinationId
                ? route('vaultix.destinations.edit', $destinationId)
                : route('vaultix.destinations.create');
            return redirect($fallbackRoute)->with('error', 'Failed to get refresh token. Make sure you chose "Consent" during login.');
        } catch (\Exception $e) {
            $fallbackRoute = $destinationId
                ? route('vaultix.destinations.edit', $destinationId)
                : route('vaultix.destinations.create');
            return redirect($fallbackRoute)->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function runNow(BackupJob $job)
    {
        // 1. Safety Check: Is there enough space to even zip the project?
        $projectSize = $this->getProjectSize();
        $diskUsage = $this->getDiskUsage();

        // We need at least 1.5x the project size free to safely create a zip
        $requiredSpace = $projectSize['total'] * 1.5;
        $freeSpaceBytes = disk_free_space(config('vaultix.monitor_path', storage_path()));

        if ($freeSpaceBytes < $requiredSpace) {
            $needed = $this->formatBytes($requiredSpace - $freeSpaceBytes);
            $msg = "Insufficient storage! You need at least {$needed} more free space to safely generate this backup.";

            // Send Email Notification if enabled for this job
            $jobRecord = \Codexalta\Vaultix\Models\BackupJob::find($job->id);
            if ($jobRecord && $jobRecord->notify_on_failure && !empty($jobRecord->notification_email)) {
                try {
                    $dashboardUrl = route('vaultix.index');
                    \Illuminate\Support\Facades\Mail::send('vaultix::emails.notification', [
                        'status' => 'failed',
                        'job' => $jobRecord,
                        'size' => null,
                        'error' => "Manual trigger aborted: " . $msg,
                        'dashboardUrl' => $dashboardUrl
                    ], function($m) use ($jobRecord) {
                        $m->to($jobRecord->notification_email)->subject("🚨 Vaultix Safety Alert: Manual Backup Aborted");
                    });
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Vaultix Manual Backup Email Failed: " . $e->getMessage());
                }
            }

            return response()->json(['error' => $msg], 422);
        }

        \Codexalta\Vaultix\Jobs\ProcessVaultixBackup::dispatch($job->id);
        VaultixActivity::log('manual_run', 'Job', $job->name, "Triggered a manual backup run.");

        return response()->json([
            'success' => 'Backup job has been dispatched to the background queue!'
        ]);
    }

    public function getLatestBackupId()
    {
        return response()->json([
            'id' => \Codexalta\Vaultix\Models\Backup::latest()->first()?->id ?? 0
        ]);
    }

    public function exportConfigs()
    {
        $data = [
            'version' => '1.0',
            'exported_at' => now()->toDateTimeString(),
            'destinations' => BackupDestination::all()->makeVisible(['credentials'])->toArray(),
            'jobs' => BackupJob::all()->toArray(),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="vaultix_configs_' . now()->format('Ymd_His') . '.json"',
        ]);
    }

    public function importConfigs(Request $request)
    {
        $request->validate(['config_file' => 'required|file|mimes:json']);

        try {
            $content = json_decode(file_get_contents($request->file('config_file')->getRealPath()), true);

            if (!$content || !isset($content['destinations'])) {
                return back()->with('error', 'Invalid configuration file.');
            }

            $destinationMap = [];

            // 1. Import Destinations
            foreach ($content['destinations'] as $destData) {
                $dest = BackupDestination::updateOrCreate(
                    ['name' => $destData['name'], 'provider' => $destData['provider']],
                    ['credentials' => $destData['credentials'], 'is_active' => $destData['is_active']]
                );
                $destinationMap[$destData['id']] = $dest->id;
            }

            // 2. Import Jobs
            foreach ($content['jobs'] as $jobData) {
                if (isset($destinationMap[$jobData['destination_id']])) {
                    $jobData['destination_id'] = $destinationMap[$jobData['destination_id']];
                    unset($jobData['id']); // Let DB generate new ID

                    // Don't duplicate exact jobs (check by name and destination)
                    BackupJob::updateOrCreate(
                        ['name' => $jobData['name'], 'destination_id' => $jobData['destination_id']],
                        $jobData
                    );
                }
            }

            return back()->with('success', 'Configurations imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadBackup(\Codexalta\Vaultix\Models\Backup $backup)
    {
        if ($backup->status !== 'success' || $backup->file_path === 'failed') {
            return back()->with('error', 'This backup file is not available.');
        }

        try {
            $command = new \Codexalta\Vaultix\Commands\VaultixBackupCommand();
            $diskConfig = $command->getDiskConfig($backup->destination);
            \Illuminate\Support\Facades\Config::set('filesystems.disks.vaultix_download', $diskConfig);
            
            // Clear the cached disk instance so Laravel builds a new adapter with the new config
            \Illuminate\Support\Facades\Storage::forgetDisk('vaultix_download');

            $disk = \Illuminate\Support\Facades\Storage::disk('vaultix_download');

            // Set cookie for JS to detect download start
            cookie()->queue('vaultix_download_started', 'true', 1, null, null, false, false);

            VaultixActivity::log('download', 'Backup', $backup->file_name, "Downloaded backup file.");

            // For GDrive and SFTP, use streamDownload with immediate flushing
            return response()->streamDownload(function () use ($disk, $backup) {
                if (ob_get_level()) ob_end_clean();

                $stream = $disk->readStream($backup->file_path);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, $backup->file_name, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $backup->file_name . '"',
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not download file: ' . $e->getMessage());
        }
    }

    public function destroyBackup(\Codexalta\Vaultix\Models\Backup $backup)
    {
        try {
            // Optional: Delete from cloud storage too
            $command = new \Codexalta\Vaultix\Commands\VaultixBackupCommand();
            $diskConfig = $command->getDiskConfig($backup->destination);
            \Illuminate\Support\Facades\Config::set('filesystems.disks.vaultix_delete', $diskConfig);
            \Illuminate\Support\Facades\Storage::forgetDisk('vaultix_delete');

            if (\Illuminate\Support\Facades\Storage::disk('vaultix_delete')->exists($backup->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('vaultix_delete')->delete($backup->file_path);
            }

            $fileName = $backup->file_name;
            $backup->delete();
            VaultixActivity::log('delete', 'Backup', $fileName, "Deleted backup record and file.");
            return back()->with('success', 'Backup record and file deleted successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Vaultix Backup Deletion Exception for file {$backup->file_name}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $backup->delete(); // Delete record anyway if file is missing
            return back()->with('success', 'Backup record removed from database (Cloud file was already missing or could not be reached).');
        }
    }

    public function removeAuthorizedEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $emails = VaultixSetting::get('authorized_emails', []);
        $emails = array_values(array_filter($emails, fn($e) => $e !== $request->email));
        VaultixSetting::set('authorized_emails', $emails);
        VaultixActivity::log('settings', 'AccessControl', $request->email, "Removed user from authorized emails.");
        return back()->with('success', 'User removed from authorized list.');
    }

    public function settings()
    {
        $authorizedEmails = VaultixSetting::get('authorized_emails', []);
        $threshold = VaultixSetting::get('storage_threshold_mb', 500);
        $timezone = VaultixSetting::get('timezone', config('app.timezone'));
        $logRetentionDays = VaultixSetting::get('log_retention_days', 30);
        $activityLogVisibility = VaultixSetting::get('activity_log_visibility', 'super_admin_only');

        $superAdmin = config('vaultix.super_admin');
        $isSuperAdmin = $superAdmin && auth()->user() && auth()->user()->email === $superAdmin;

        $diskUsage = $this->getDiskUsage();

        return view('vaultix::settings', compact('authorizedEmails', 'threshold', 'diskUsage', 'timezone', 'logRetentionDays', 'activityLogVisibility', 'isSuperAdmin'));
    }

    public function updateTimezone(Request $request)
    {
        VaultixSetting::set('timezone', $request->timezone);
        VaultixActivity::log('settings', 'Timezone', $request->timezone, "Updated system timezone.");
        return back()->with('success', 'Timezone updated successfully!');
    }

    public function updateLogRetention(Request $request)
    {
        // Only super admin can change log retention
        $superAdmin = config('vaultix.super_admin');
        if (!$superAdmin || !auth()->user() || auth()->user()->email !== $superAdmin) {
            abort(403, 'Only the Super Admin can update Activity Log Retention.');
        }
        $request->validate(['days' => 'required|integer|min:1']);
        VaultixSetting::set('log_retention_days', $request->days);
        VaultixActivity::log('settings', 'Retention', $request->days . ' Days', 'Updated activity log retention period.');
        return back()->with('success', 'Log retention period updated!');
    }

    public function updateActivityVisibility(Request $request)
    {
        // Only super admin can change this
        $superAdmin = config('vaultix.super_admin');
        if (!$superAdmin || !auth()->user() || auth()->user()->email !== $superAdmin) {
            abort(403, 'Only the Super Admin can update this setting.');
        }
        $request->validate(['visibility' => 'required|in:super_admin_only,all_authorized']);
        VaultixSetting::set('activity_log_visibility', $request->visibility);
        VaultixActivity::log('settings', 'ActivityLog', $request->visibility, 'Updated activity log visibility setting.');
        return back()->with('success', 'Activity log visibility updated!');
    }

    public function activities(Request $request)
    {
        // Check activity log visibility setting
        $superAdmin = config('vaultix.super_admin');
        $isSuperAdmin = $superAdmin && auth()->user() && auth()->user()->email === $superAdmin;
        $visibility = VaultixSetting::get('activity_log_visibility', 'super_admin_only');

        if (!$isSuperAdmin && $visibility === 'super_admin_only') {
            abort(403, 'Activity logs are restricted to Super Admin only.');
        }

        $query = VaultixActivity::orderBy('created_at', 'desc');

        // Apply Filters
        if ($request->filled('user')) {
            $query->where('user_email', $request->user);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('type')) {
            $query->where('entity_type', $request->type);
        }

        $activities = $query->paginate(20)->withQueryString();

        // Get unique users and actions for filter dropdowns
        $users = VaultixActivity::select('user_email', 'user_name')->distinct()->get();
        $actions = VaultixActivity::select('action')->distinct()->get();
        $types = VaultixActivity::select('entity_type')->distinct()->get();

        return view('vaultix::activities', compact('activities', 'users', 'actions', 'types'));
    }

    public function exportActivities(Request $request)
    {
        // Only super admin
        $superAdmin = config('vaultix.super_admin');
        if (!$superAdmin || auth()->user()->email !== $superAdmin) {
            abort(403);
        }

        $query = VaultixActivity::orderBy('created_at', 'desc');

        // Apply Filters (same as activities method)
        if ($request->filled('user')) $query->where('user_email', $request->user);
        if ($request->filled('action')) $query->where('action', $request->action);
        if ($request->filled('type')) $query->where('entity_type', $request->type);

        $activities = $query->get();
        $format = $request->get('format', 'csv');
        $filename = "vaultix_activities_" . now()->format('Y-m-d_His') . "." . $format;

        if ($format === 'json') {
            return response($activities->toJson(JSON_PRETTY_PRINT), 200, [
                "Content-type"        => "application/json",
                "Content-Disposition" => "attachment; filename=$filename",
            ]);
        }

        // Default to CSV
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Date', 'User', 'Email', 'Action', 'Entity', 'Entity Name', 'IP Address', 'Description', 'Old Data', 'New Data'];

        $callback = function() use($activities, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($activities as $activity) {
                fputcsv($file, [
                    $activity->created_at->toDateTimeString(),
                    $activity->user_name,
                    $activity->user_email,
                    strtoupper($activity->action),
                    $activity->entity_type,
                    $activity->entity_name,
                    $activity->ip_address,
                    $activity->description,
                    $activity->old_data ? json_encode($activity->old_data, JSON_UNESCAPED_SLASHES) : '',
                    $activity->new_data ? json_encode($activity->new_data, JSON_UNESCAPED_SLASHES) : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function updateAuthorizedEmails(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $emails = VaultixSetting::get('authorized_emails', []);

        if (!in_array($request->email, $emails)) {
            $emails[] = $request->email;
            VaultixSetting::set('authorized_emails', $emails);
            VaultixActivity::log('settings', 'AccessControl', $request->email, "Added new authorized user.");
        }

        return back()->with('success', 'User added to authorized list.');
    }

    public function updateThreshold(Request $request)
    {
        $request->validate(['threshold' => 'required|integer|min:100']);
        VaultixSetting::set('storage_threshold_mb', $request->threshold);
        VaultixActivity::log('settings', 'Threshold', $request->threshold . ' MB', "Updated storage alert threshold.");

        return back()->with('success', 'Storage alert threshold updated.');
    }

    private function getDiskUsage()
    {
        // Use configurable path from vaultix.php (defaults to storage_path)
        $path = config('vaultix.monitor_path', storage_path());

        // Ensure path exists to avoid errors
        if (!is_dir($path)) $path = '/';

        $total = @disk_total_space($path) ?: 0;
        $free = @disk_free_space($path) ?: 0;
        $used = $total - $free;
        $percentage = ($total > 0) ? ($used / $total) * 100 : 0;

        $thresholdMb = VaultixSetting::get('storage_threshold_mb', 500);
        $freeMb = $free / 1024 / 1024;

        return [
            'total' => $this->formatBytes($total),
            'free' => $this->formatBytes($free),
            'used' => $this->formatBytes($used),
            'percentage' => round($percentage, 1),
            'is_low' => $freeMb < $thresholdMb,
            'threshold_mb' => $thresholdMb,
            'free_mb' => round($freeMb, 0)
        ];
    }

    private function getProjectSize()
    {
        // 1. Database Size (Accurate DB size from schema)
        $dbName = config('database.connections.' . config('database.default') . '.database');
        $dbSize = 0;
        try {
            $result = \DB::select("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ?", [$dbName]);
            $dbSize = (int) ($result[0]->size ?? 0);
        } catch (\Exception $e) {}

        // 2. File Size Calculation
        $basePath = base_path();
        $fileSize = 0;

        // On Linux/WSL, use 'du' command for speed and accuracy
        if (function_exists('exec') && PHP_OS_FAMILY !== 'Windows') {
            try {
                // Exclude heavy folders using du syntax
                $excludeFolders = ['vendor', 'node_modules', '.git', 'storage/app/backup-temp', 'storage/framework/cache'];
                $excludeCmd = "";
                foreach($excludeFolders as $ex) {
                    $excludeCmd .= " --exclude='" . $ex . "'";
                }

                $output = exec("du -sb " . escapeshellarg($basePath) . $excludeCmd);
                if ($output) {
                    $fileSize = (int) explode("\t", $output)[0];
                }
            } catch (\Exception $e) {
                $fileSize = 0; // Fallback to PHP manual scan
            }
        }

        // Fallback or Windows: Manual Recursive Scan
        if ($fileSize <= 0) {
            $fileSize = $this->getDirSize($basePath, [
                base_path('vendor'),
                base_path('node_modules'),
                base_path('.git'),
                storage_path('app/backup-temp'),
                storage_path('framework/cache'),
            ]);
        }

        return [
            'db' => $dbSize,
            'files' => $fileSize,
            'total' => $dbSize + $fileSize,
            'formatted' => $this->formatBytes($dbSize + $fileSize)
        ];
    }

    private function getDirSize($path, $exclude = [])
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $filePath = $file->getRealPath();
            $shouldExclude = false;
            foreach ($exclude as $exPath) {
                if (strpos($filePath, $exPath) === 0) {
                    $shouldExclude = true;
                    break;
                }
            }
            if (!$shouldExclude) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    protected function calculateNextRunAt($frequency, $time, $day = null)
    {
        $next = \Carbon\Carbon::createFromFormat('H:i', $time);

        if ($next->isPast() && !in_array($frequency, ['hourly', '6_hours', '12_hours'])) {
            $next->addDay();
        }

        if ($frequency === 'hourly') {
            return now()->addHour()->startOfHour();
        }

        if ($frequency === '6_hours') {
            return now()->addHours(6);
        }

        if ($frequency === '12_hours') {
            return now()->addHours(12);
        }

        if ($frequency === 'weekly' && $day) {
            $next = \Carbon\Carbon::parse("next $day $time");
        }

        if ($frequency === 'monthly' && $day) {
            if ($day === 'last') {
                $next = \Carbon\Carbon::parse("last day of this month $time");
                if ($next->isPast()) {
                    $next = \Carbon\Carbon::parse("last day of next month $time");
                }
            } else {
                $next = \Carbon\Carbon::parse("this month $day $time");
                if ($next->isPast()) {
                    $next = \Carbon\Carbon::parse("next month $day $time");
                }
            }
        }

        return $next;
    }
}

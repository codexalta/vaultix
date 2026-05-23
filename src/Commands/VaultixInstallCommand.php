<?php

namespace Codexalta\Vaultix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class VaultixInstallCommand extends Command
{
    protected $signature = 'vaultix:install';

    protected $description = 'Install and setup Vaultix (only runs Vaultix package migrations)';

    public function handle()
    {
        $this->info('Starting Vaultix installation...');

        $this->info('Publishing configuration...');
        Artisan::call('vendor:publish', [
            '--tag'   => 'vaultix-config',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        $this->info('Running Vaultix migrations...');
        $this->runVaultixMigrations();

        $this->info('Clearing application caches...');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        $this->line('Caches cleared successfully.');

        $this->info('Restarting queue workers...');
        Artisan::call('queue:restart');
        $this->line('Queue workers restarted.');

        $this->info('Vaultix installation completed successfully!');
        $this->comment('Make sure your Laravel Scheduler (schedule:run) and Queue Worker (queue:work) are running.');

        return self::SUCCESS;
    }

    /**
     * Run only the migrations located inside the Vaultix package directory.
     * This avoids accidentally running the host project's own pending migrations.
     */
    protected function runVaultixMigrations(): void
    {
        // Resolve the migration path inside the package
        $migrationPath = realpath(__DIR__ . '/../../database/migrations')
            ?: realpath(__DIR__ . '/../Database/Migrations');

        if (!$migrationPath || !is_dir($migrationPath)) {
            $this->error('Vaultix migration directory not found. Skipping migrations.');
            return;
        }

        // Collect all Vaultix migration file names
        $migrationFiles = glob($migrationPath . '/*.php');

        if (empty($migrationFiles)) {
            $this->warn('No Vaultix migration files found.');
            return;
        }

        // Check which ones have already been run via the migrations table
        $alreadyRan = [];
        try {
            $alreadyRan = DB::table('migrations')->pluck('migration')->toArray();
        } catch (\Exception $e) {
            // migrations table might not exist yet — fine, it will be created
        }

        $pendingFiles = array_filter($migrationFiles, function ($file) use ($alreadyRan) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            return !in_array($name, $alreadyRan);
        });

        if (empty($pendingFiles)) {
            $this->line('All Vaultix migrations are already up to date.');
            return;
        }

        // Run migrate scoped only to the package migration path
        $relativePath = ltrim(
            str_replace(base_path(), '', $migrationPath),
            DIRECTORY_SEPARATOR . '/'
        );

        Artisan::call('migrate', [
            '--path'  => $relativePath,
            '--force' => true,
        ]);

        $this->line(Artisan::output());
    }
}

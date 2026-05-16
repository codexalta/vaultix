<?php

namespace Codexalta\Vaultix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class VaultixInstallCommand extends Command
{
    protected $signature = 'vaultix:install';

    protected $description = 'Install and setup Vaultix';

    public function handle()
    {
        $this->info('Starting Vaultix installation...');

        $this->info('Publishing configuration...');
        Artisan::call('vendor:publish', [
            '--tag' => 'vaultix-config',
            '--force' => true
        ]);
        $this->line(Artisan::output());

        $this->info('Running migrations...');
        Artisan::call('migrate');
        $this->line(Artisan::output());

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
}

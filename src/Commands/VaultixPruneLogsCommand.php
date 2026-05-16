<?php

namespace Codexalta\Vaultix\Commands;

use Illuminate\Console\Command;
use Codexalta\Vaultix\Models\VaultixActivity;
use Codexalta\Vaultix\Models\VaultixSetting;
use Carbon\Carbon;

class VaultixPruneLogsCommand extends Command
{
    protected $signature = 'vaultix:prune-logs';
    protected $description = 'Remove old activity logs based on retention settings';

    public function handle()
    {
        $days = VaultixSetting::get('log_retention_days', 30);
        $count = VaultixActivity::where('created_at', '<', Carbon::now()->subDays($days))->delete();

        $this->info("Deleted {$count} old activity logs.");
    }
}

<?php

namespace Codexalta\Vaultix\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Codexalta\Vaultix\Models\BackupJob;

class ProcessVaultixBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;

    public function __construct($jobId = null)
    {
        $this->jobId = $jobId;
    }

    public function handle()
    {
        $params = [];
        if ($this->jobId) {
            $params['--job'] = $this->jobId;
        }

        Artisan::call('vaultix:run', $params);
    }
}

<?php

namespace Codexalta\Vaultix\Models;

use Illuminate\Database\Eloquent\Model;

class BackupJob extends Model
{
    protected $table = 'vaultix_jobs';
    protected $fillable = [
        'destination_id',
        'name',
        'type',
        'custom_folder_name',
        'notification_email',
        'notify_on_success',
        'notify_on_failure',
        'frequency',
        'backup_time',
        'backup_day',
        'last_run_at',
        'next_run_at',
        'is_enabled',
        'keep_all_backups_for_days',
        'keep_daily_backups_for_days',
        'keep_weekly_backups_for_weeks',
        'keep_monthly_backups_for_months',
    ];

    public function destination() {
        return $this->belongsTo(BackupDestination::class, 'destination_id');
    }

    public function backups() {
        return $this->hasMany(Backup::class, 'job_id')->latest();
    }
}

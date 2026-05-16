<?php

namespace Codexalta\Vaultix\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $table = 'vaultix_backups';

    protected $fillable = [
        'job_id',
        'destination_id',
        'file_path',
        'file_name',
        'file_size',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(BackupJob::class, 'job_id');
    }

    public function destination()
    {
        return $this->belongsTo(BackupDestination::class, 'destination_id');
    }
    
    /**
     * Get size in human readable format
     */
    public function getHumanSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024; $i++) $bytes /= 1024;
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

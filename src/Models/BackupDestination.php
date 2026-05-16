<?php

namespace Codexalta\Vaultix\Models;

use Illuminate\Database\Eloquent\Model;

class BackupDestination extends Model
{
    protected $table = 'vaultix_destinations';
    protected $guarded = [];
    protected $casts = ['credentials' => 'array'];

    public function jobs() {
        return $this->hasMany(BackupJob::class, 'destination_id');
    }
}

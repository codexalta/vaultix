<?php

namespace Codexalta\Vaultix\Models;

use Illuminate\Database\Eloquent\Model;

class VaultixActivity extends Model
{
    protected $table = 'vaultix_activities';
    
    protected $fillable = [
        'user_id', 'user_name', 'user_email', 'action', 
        'entity_type', 'entity_name', 'description', 
        'old_data', 'new_data',
        'ip_address', 'user_agent'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public static function log($action, $entityType = null, $entityName = null, $description = null, $oldData = null, $newData = null)
    {
        $user = auth()->user();
        
        return self::create([
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'System',
            'user_email' => $user ? $user->email : 'system@vaultix',
            'action' => $action,
            'entity_type' => $entityType,
            'entity_name' => $entityName,
            'description' => $description,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

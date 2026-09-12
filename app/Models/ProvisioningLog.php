<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProvisioningLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'step',
        'status',
        'message',
        'error',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

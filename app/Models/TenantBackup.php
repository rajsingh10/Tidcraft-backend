<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantBackup extends Model
{
    protected $fillable = [
        'tenant_id',
        'file_name',
        'file_path',
        'file_size'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

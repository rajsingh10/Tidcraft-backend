<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantDatabase extends Model
{
    protected $fillable = [
        'tenant_id',
        'database_name',
        'database_host',
        'database_port',
        'database_username',
        'encrypted_database_password',
        'status',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

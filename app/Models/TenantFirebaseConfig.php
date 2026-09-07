<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantFirebaseConfig extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \App\Traits\Auditable;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'api_key',
        'app_id',
        'auth_domain',
        'storage_bucket',
        'messaging_sender_id',
        'database_url',
        'create_by',
        'update_by',
        'delete_by',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

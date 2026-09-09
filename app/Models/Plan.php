<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \App\Traits\Auditable;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle',
        'is_active',
        'features',
        'is_popular',
        'max_users',
        'max_orders',
        'storage_gb',
        'duration_days',
        'create_by',
        'update_by',
        'delete_by',
    ];

    protected $casts = [
        'features' => 'array',
        'is_popular' => 'boolean',
    ];
}

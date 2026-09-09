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
        'product_id',
        'name',
        'description',
        'price',
        'billing_cycle',
        'is_active',
        'features',
        'integrations',
        'is_popular',
        'max_users',
        'max_orders',
        'additional_order_price',
        'store_configuration',
        'has_hybrid_customer_app',
        'has_hybrid_customer_merchant_app',
        'has_unlimited_users_listings',
        'has_white_labeled_solution',
        'has_white_labeled_dashboard',
        'storage_gb',
        'duration_days',
        'create_by',
        'update_by',
        'delete_by',
    ];

    protected $casts = [
        'features' => 'array',
        'integrations' => 'array',
        'is_popular' => 'boolean',
        'has_hybrid_customer_app' => 'boolean',
        'has_hybrid_customer_merchant_app' => 'boolean',
        'has_unlimited_users_listings' => 'boolean',
        'has_white_labeled_solution' => 'boolean',
        'has_white_labeled_dashboard' => 'boolean',
    ];
}

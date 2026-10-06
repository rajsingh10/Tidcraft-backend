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
        'currency_id',
        'currency_code',
        'name',
        'description',
        'monthly_price',
        'annual_price',
        'onboarding_fee',
        'is_active',
        'onboard',
        'features',
        'integrations',
        'is_popular',
        'max_users',
        'max_users_annual',
        'max_locations',
        'max_orders',
        'max_orders_monthly',
        'max_bookings',
        'max_bookings_monthly',
        'additional_order_price',
        'additional_booking_price',
        'store_configuration',
        'has_hybrid_customer_app',
        'has_hybrid_customer_merchant_app',
        'has_unlimited_users_listings',
        'has_white_labeled_solution',
        'has_white_labeled_dashboard',
        'has_customer_app',
        'has_merchant_app',
        'has_rider_app',
        'has_white_labeled_app',
        'has_watchman_app',
        'has_owner_app',
        'storage_gb',
        'storage_gb_annual',
        'duration_days',
        'create_by',
        'update_by',
        'delete_by',
    ];

    protected $casts = [
        'currency_id' => 'integer',
        'features' => 'array',
        'integrations' => 'array',
        'is_popular' => 'boolean',
        'onboard' => 'boolean',
        'has_hybrid_customer_app' => 'boolean',
        'has_hybrid_customer_merchant_app' => 'boolean',
        'has_unlimited_users_listings' => 'boolean',
        'has_white_labeled_solution' => 'boolean',
        'has_white_labeled_dashboard' => 'boolean',
        'has_customer_app' => 'boolean',
        'has_merchant_app' => 'boolean',
        'has_rider_app' => 'boolean',
        'has_white_labeled_app' => 'array',
        'has_watchman_app' => 'boolean',
        'has_owner_app' => 'boolean',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function prices()
    {
        return $this->hasMany(PlanPrice::class, 'plan_id');
    }
}

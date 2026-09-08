<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \App\Traits\Auditable;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $fillable = [
        'uuid',
        'business_name',
        'primary_contact_email',
        'phone_number',
        'address',
        'industry',
        'product_id',
        'plan_id',
        'status',
        'create_by',
        'update_by',
        'delete_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function domain()
    {
        return $this->hasOne(TenantDomain::class);
    }

    public function firebaseConfig()
    {
        return $this->hasOne(TenantFirebaseConfig::class);
    }

    public function addOns()
    {
        return $this->belongsToMany(AddOn::class, 'tenant_add_on');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}

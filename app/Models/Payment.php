<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \App\Traits\Auditable;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'bank_rrn',
        'order_id',
        'amount',
        'currency',
        'billing_cycle',
        'payment_method',
        'status',
        'customer_details',
        'type',
        'metadata',
        'create_by',
        'update_by',
        'delete_by',
    ];

    protected $casts = [
        'customer_details' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = ['invoice_number'];

    public function getInvoiceNumberAttribute()
    {
        $issueDate = $this->create_at ?? $this->created_at ?? now();
        return 'INV-' . \Carbon\Carbon::parse($issueDate)->format('Y') . '-' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    public function getCreatedAtAttribute()
    {
        $val = $this->attributes['create_at'] ?? $this->attributes['created_at'] ?? null;
        return $val ? \Carbon\Carbon::parse($val) : now();
    }

    public function getUpdatedAtAttribute()
    {
        $val = $this->attributes['update_at'] ?? $this->attributes['updated_at'] ?? null;
        return $val ? \Carbon\Carbon::parse($val) : now();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

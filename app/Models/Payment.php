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

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

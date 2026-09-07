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
        'amount',
        'currency',
        'payment_method',
        'status',
        'create_by',
        'update_by',
        'delete_by',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

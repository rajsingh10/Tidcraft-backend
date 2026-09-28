<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class PlanPrice extends Model
{
    use SoftDeletes;
    use Auditable;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $fillable = [
        'plan_id',
        'currency_id',
        'monthly_price',
        'annual_price',
        'create_by',
        'update_by',
        'delete_by',
    ];

    protected $casts = [
        'plan_id' => 'integer',
        'currency_id' => 'integer',
        'monthly_price' => 'float',
        'annual_price' => 'float',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}

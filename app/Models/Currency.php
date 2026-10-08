<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Currency extends Model
{
    use SoftDeletes;
    use Auditable;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
        'create_by',
        'update_by',
        'delete_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['status'];

    public function getStatusAttribute(): string
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    public function plans()
    {
        return $this->hasMany(Plan::class, 'currency_id');
    }
}

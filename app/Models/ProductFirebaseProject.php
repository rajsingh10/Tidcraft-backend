<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFirebaseProject extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'service_account_json',
    ];

    protected $appends = [
        'has_service_account',
    ];

    protected $casts = [
        'service_account_json' => 'encrypted',
    ];

    public function getHasServiceAccountAttribute(): bool
    {
        return !empty($this->attributes['service_account_json']);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

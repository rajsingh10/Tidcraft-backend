<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFirebaseProject extends Model
{
    use \App\Traits\Auditable;

    protected $guarded = [];

    protected $hidden = [
        'service_account_json',
    ];

    protected $appends = [
        'has_service_account',
        'firebase_db_collection_url',
    ];

    protected $casts = [
        'service_account_json' => 'encrypted',
        'firebase_db_collection' => 'string',
    ];

    public function getFirebaseDbCollectionUrlAttribute()
    {
        return $this->firebase_db_collection ? url('storage/' . $this->firebase_db_collection) : null;
    }

    public function getHasServiceAccountAttribute(): bool
    {
        return !empty($this->attributes['service_account_json']);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

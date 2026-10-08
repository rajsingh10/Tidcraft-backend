<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDemo extends Model
{
    protected $fillable = [
        'product_id',
        'title',
        'platform',
        'demo_url',
        'demo_id',
        'demo_password',
        'screenshots',
        'app_store_url',
        'play_store_url',
        'is_active',
    ];

    protected $casts = [
        'screenshots' => 'array',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

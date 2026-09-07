<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductCategory extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \App\Traits\Auditable;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $fillable = [
        'name',
        'description',
        'image',
        'is_active',
        'create_by',
        'update_by',
        'delete_by',
    ];

    /**
     * Get the full URL for the image.
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? url('storage/' . $value) : null,
        );
    }
}

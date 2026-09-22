<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class CmsPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'content',
        'featured_image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'sort_order',
        'is_active',
        'page_type',
        'product_id',
        'published_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'published_at' => 'datetime',
        'content'      => 'array',
        'product_id'   => 'integer',
    ];

    /**
     * The product this CMS page belongs to (null for home-page sections).
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: home-page sections only.
     */
    public function scopeHomePage($query)
    {
        return $query->where('page_type', 'home')->whereNull('product_id');
    }

    /**
     * Scope: sections for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('page_type', 'product')->where('product_id', $productId);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                $model->save();
            }
        });

        // Audit Logs
        static::created(function ($model) {
            self::logAudit('created', $model);
        });

        static::updated(function ($model) {
            self::logAudit('updated', $model);
        });

        static::deleted(function ($model) {
            self::logAudit('deleted', $model);
        });
    }

    protected static function logAudit($action, $model)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'CmsPage',
            'action' => $action,
            'description' => "CmsPage {$model->id} was {$action}",
            'old_values' => $action === 'updated' ? $model->getOriginal() : null,
            'new_values' => $action !== 'deleted' ? $model->getAttributes() : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

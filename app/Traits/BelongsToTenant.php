<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\App;

trait BelongsToTenant
{
    /**
     * Boot the trait to automatically apply the global scope and set the tenant_id on creation.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (App::has('tenant_id') && !$model->tenant_id) {
                $model->tenant_id = App::get('tenant_id');
            }
        });
    }
}

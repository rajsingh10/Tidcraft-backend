<?php 

namespace App\Traits; 

use App\Services\AuditLogger; 

trait Auditable 
{ 
    public static function bootAuditable() 
    { 
        static::creating(function ($model) { 
            if (auth()->check()) { 
                $model->create_by = auth()->id(); 
                $model->update_by = auth()->id(); 
            } 
        }); 
        
        static::updating(function ($model) { 
            if (auth()->check()) { 
                $model->update_by = auth()->id(); 
            } 
        }); 
        
        static::deleting(function ($model) { 
            if (auth()->check() && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) { 
                $model->delete_by = auth()->id(); 
                $model->saveQuietly(); 
            } 
        }); 
        
        static::created(function ($model) { 
            AuditLogger::log(class_basename($model) . ' Created', 'Insert', 'A new ' . class_basename($model) . ' was inserted.', null, $model->getAttributes()); 
        }); 
        
        static::updated(function ($model) { 
            AuditLogger::log(class_basename($model) . ' Updated', 'Update', 'A ' . class_basename($model) . ' was modified.', $model->getOriginal(), $model->getChanges()); 
        }); 
        
        static::deleted(function ($model) { 
            AuditLogger::log(class_basename($model) . ' Deleted', 'Delete', 'A ' . class_basename($model) . ' was deleted.', $model->getAttributes(), null); 
        }); 
        
        static::retrieved(function ($model) { 
            AuditLogger::log(class_basename($model) . ' Viewed', 'View', 'A ' . class_basename($model) . ' record was viewed.', null, null); 
        }); 
    } 
}

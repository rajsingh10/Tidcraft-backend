<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'app_name',
        'email',
        'password',
        'apk_url',
        'web_url',
    ];

    protected $casts = [
        'apk_url' => 'array',
        'web_url' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'related_id',
        'client_name',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}

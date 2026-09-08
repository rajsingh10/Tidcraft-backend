<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \App\Traits\Auditable;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $fillable = [
        'customer_name',
        'email',
        'phone',
        'project_id',
        'description',
        'create_by',
        'update_by',
        'delete_by',
    ];
}

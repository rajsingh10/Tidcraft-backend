<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerCost extends Model
{
    protected $fillable = [
        'month_year',
        'aws_cost',
        'firebase_1_cost',
        'firebase_2_cost',
        'server_infrastructure_cost',
    ];
}

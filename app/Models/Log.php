<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'action',
        'model',
        'model_id',
        'data',
        'user_id',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}

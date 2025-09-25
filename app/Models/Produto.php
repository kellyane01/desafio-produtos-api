<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $table = 'produtos';

    protected $fillable = [
        'nome',
        'descricao',
        'preco',
        'categoria',
        'estoque',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'estoque' => 'integer',
    ];
}

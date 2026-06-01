<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Initiative extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'icon',
        'color',
        'summary',
        'description',
        'metrics',
        'image',
        'order',
        'is_active',
    ];

    protected $casts = [
        'metrics' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
}

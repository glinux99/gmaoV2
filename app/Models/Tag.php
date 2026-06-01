<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    /**
     * Convertir automatiquement certaines colonnes.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relation : Un tag appartient à plusieurs articles (Many-to-Many).
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}

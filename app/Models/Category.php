<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes; // <-- Ajout de SoftDeletes

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'is_active', // <-- Ajouté d'après votre migration
    ];

    /**
     * Convertir automatiquement certaines colonnes.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relation : Une catégorie contient plusieurs articles.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

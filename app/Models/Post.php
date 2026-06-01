<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Contracts\MediaLibraryRequest;

class Post extends Model implements HasMedia
{
    use HasFactory, SoftDeletes,InteractsWithMedia;

    /**
     * Les attributs assignables en masse (Mass Assignment).
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'cover_image',
        'status',
        'is_featured',
        'published_at',
        'seo_title',
        'seo_description',
        'views',
        'likes',
        'author_id',
        'category_id',
    ];

    /**
     * Conversion automatique des types de données (Casting).
     */
    protected $casts = [
        'published_at' => 'datetime', // Transforme en objet Carbon automatiquement
        'is_featured' => 'boolean',
        'views' => 'integer',
        'likes' => 'integer',
    ];

    /**
     * Scope : Récupérer uniquement les articles publiés et dont la date est passée.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    // ==========================================
    // RELATIONS
    // ==========================================

    /**
     * L'auteur de l'article (Relation One-to-Many inverse)
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * La catégorie de l'article (Relation One-to-Many inverse)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Les tags (mots-clés) de l'article (Relation Many-to-Many)
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Les commentaires de l'article (Relation One-to-Many)
     */
   public function comments()
{
    return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
}
}

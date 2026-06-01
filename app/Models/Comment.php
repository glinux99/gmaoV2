<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    /**
     * Les attributs assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'author_name',
        'author_email',
        'content',
        'is_approved',
        'parent_id',          // Pour les réponses aux commentaires (si besoin)
        'likes',              // Compteur de likes sur le commentaire
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_approved' => 'boolean',
        'likes' => 'integer',
    ];

    // ==================== RELATIONS ====================

    /**
     * Relation : un commentaire appartient à un post.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Relation : un commentaire peut être écrit par un utilisateur enregistré.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : un commentaire peut avoir un commentaire parent (réponse).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Relation : un commentaire peut avoir des réponses.
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope : récupérer uniquement les commentaires approuvés.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope : récupérer les commentaires les plus récents en premier.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ==================== MUTATEURS / ACCESSOIRS ====================

    /**
     * Accesseur : retourne le nom de l'auteur (priorité à l'utilisateur connecté).
     */
    public function getDisplayNameAttribute()
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }
        return $this->author_name ?? 'Anonyme';
    }

    /**
     * Accesseur : retourne l'avatar de l'auteur.
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->user_id && $this->user && $this->user->avatar) {
            return $this->user->avatar;
        }
        return null;
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Vérifie si le commentaire peut être modifié par un utilisateur.
     */
    public function isEditableBy($userId)
    {
        return $this->user_id === $userId;
    }
}

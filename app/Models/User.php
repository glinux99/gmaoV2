<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',          // Équipe principale (optionnel)
        'name',             // Prénom
        'last_name',        // Nom
        'email',
        'password',
        'provider_name',
        'provider_id',
        'hourly_rate',
        'position',
        'phone',
        'contract_type',
        'hiring_date',
        'linkedin_url',
        'bio',
        'avatar',           // Chemin local (fallback)
        'is_active',
        'status',           // online/offline/busy/away
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url', // URL de l’avatar (Spatie Media)
        'full_name',         // Nom complet
    ];
 public function likedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_likes', 'user_id', 'post_id')
                    ->withTimestamps();
    }
    // ==================== RELATIONS ====================

    /**
     * Connexions enregistrées (pour journalisation).
     */
    public function logins(): HasMany
    {
        return $this->hasMany(Login::class);
    }

    /**
     * Région géographique (optionnelle).
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Équipes auxquelles l’utilisateur appartient (relation many-to-many).
     */
    public function team(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user');
    }

    /**
     * Activités créées par l’utilisateur.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'user_id');
    }

    // ==================== CHAT ====================

    /**
     * Conversations (canaux publics/privés et messages directs).
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
                    ->withPivot('last_read_at', 'is_muted')
                    ->withTimestamps();
    }

    /**
     * Messages envoyés par l’utilisateur.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Réactions envoyées par l’utilisateur sur des messages.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Récupère le nombre de messages non lus dans toutes les conversations.
     */
    public function unreadMessagesCount(): int
    {
        $total = 0;
        foreach ($this->conversations as $conv) {
            $total += $conv->unread_count_for_user($this->id);
        }
        return $total;
    }

    /**
     * Retourne la liste des permissions sous forme de tableau associatif.
     */
    public function getPermissionArray(): array
    {
        return $this->getAllPermissions()->mapWithKeys(fn($pr) => [$pr['name'] => true])->toArray();
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Accesseur pour l’avatar (URL).
     * Priorité à l’image depuis Spatie Media, sinon le champ `avatar` (chemin local), sinon null.
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->profile_photo_url) {
                    return $this->profile_photo_url;
                }
                return $value ? asset('storage/' . $value) : null;
            }
        );
    }

    /**
     * Accesseur pour l’URL de la photo de profil (Spatie Media).
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        $media = $this->getLastMediaUrl('avatar');
        return $media ?: null;
    }

    /**
     * Accesseur pour le nom complet.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->name . ' ' . $this->last_name);
    }

    /**
     * Formate la date de création pour l’API (ISO 8601, mais adaptable).
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    // Garder les accesseurs personnalisés si vous voulez un format spécifique
    // (ils ne doivent pas interférer avec la sérialisation par défaut)
    public function getCreatedAtFrenchAttribute()
    {
        return $this->created_at?->format('d-m-Y H:i');
    }

    public function getUpdatedAtFrenchAttribute()
    {
        return $this->updated_at?->format('d-m-Y H:i');
    }
}

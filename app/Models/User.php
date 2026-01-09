<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;


use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles,InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
         'provider_name',
        'provider_id',
        'fonction',
        'numero',
        'region_id',
        'pointure',
        'size',
        'profile_photo',
        'region_id'

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
    ];

    // Expose Spatie Media avatar URL as profile_photo_url in arrays/JSON
    protected $appends = [
        'profile_photo_url',
    ];
 public function logins(): HasMany
    {
        return $this->hasMany(Login::class);
    }
    public function getCreatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['created_at']));
    }

    public function getUpdatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['updated_at']));
    }

    public function getEmailVerifiedAtAttribute()
    {
        return $this->attributes['email_verified_at'] == null ? null:date('d-m-Y H:i', strtotime($this->attributes['email_verified_at']));
    }

    public function getPermissionArray()
    {
        return $this->getAllPermissions()->mapWithKeys(function ($pr) {
            return [$pr['name'] => true];
        });
    }
        public function region()
{
    return $this->belongsTo(Region::class);
}

// Dans app/Models/User.php

/**
 * Les équipes auxquelles l'utilisateur appartient.
 */

public function teams()
{
    return $this->belongsToMany(Team::class);
}

/**
 * Accessor: profile_photo_url from Spatie Media (collection 'avatar').
 */
public function getProfilePhotoUrlAttribute(): ?string
{
    if (method_exists($this, 'getFirstMediaUrl')) {
        $url = $this->getFirstMediaUrl('avatar');
        return $url ?: null;
    }
    return null;
}

}

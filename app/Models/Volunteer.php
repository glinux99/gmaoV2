<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Volunteer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'description',
        'skills',
        'photo',
        'is_active',
        'order',
    ];

    protected $casts = [
        'skills'    => 'array',
        'is_active' => 'boolean',
        'order'     => 'integer',
    ];

    // Attributs ajoutés automatiquement dans les réponses JSON
    protected $appends = ['photo_url', 'skills_list'];

    /**
     * URL complète de la photo (ou null si aucune).
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }

    /**
     * Liste des compétences sous forme de tableau (pour l'affichage).
     */
    public function getSkillsListAttribute(): array
    {
        return $this->skills ?? [];
    }
}

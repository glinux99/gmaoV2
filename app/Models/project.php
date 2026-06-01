<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'province_id',
        'progress',
        'target',
        'lead',
        'budget',
        'image',
        'is_active','order','category_id',
        'description',
        'start_date',
        'end_date',
        'status'
    ];

    protected $casts = [
        'progress' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relation avec la province
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Équipe assignée au projet (Membres).
     */
    public function team(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user');
    }

    // Accesseur pour obtenir le label de la province (utilisé dans le front)
    public function getProvinceLabelAttribute(): string
    {
        return $this->province?->name ?? '';
    }
}

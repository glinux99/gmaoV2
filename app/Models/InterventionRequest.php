<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InterventionRequest extends Model
{
    use HasFactory;

    // Définissez les champs que vous pouvez assigner en masse (Mass Assignable)
    protected $fillable = [
        'title',
        'description',
        'requested_by_type', // Pour la relation polymorphe
        'requested_by_id',   // Pour la relation polymorphe
        'region_id',
        'location_details',
        'status', // Ex: 'Ouverte', 'En cours', 'Fermée'
        'priority', // Ex: 'Faible', 'Moyenne', 'Urgent'
        'reported_at', // Date et heure de signalement
        'validator_id',
        'validated_at',
        'validation_notes',
        'closed_at', // Date et heure de résolution
    ];

    // Définissez les casts pour les dates
    protected $casts = [
        'reported_at' => 'datetime',
        'closed_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    // --- RELATIONS ---

    /**
     * Obtient le modèle qui a créé la demande d'intervention (ex: User, Client).
     */
    public function requestedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Obtient la région à laquelle la demande est associée.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Obtient le superviseur qui a validé la demande.
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    /**
     * Obtient l'opération de maintenance générée à partir de cette demande.
     * * Cette relation est essentielle pour lier la demande à la planification de la maintenance.
     */
    public function maintenance(): HasOne
    {
        // Supposons qu'une demande d'intervention peut générer au maximum une seule maintenance.
        return $this->hasOne(Maintenance::class);
    }

    // Vous pourriez ajouter d'autres relations HasMany ici (ex: commentaires, logs d'activité)
    /*
    public function comments(): HasMany
    {
        return $this->hasMany(InterventionComment::class);
    }
    */
}

<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InterventionRequest extends Model
{
    use HasFactory;

    // Définissez les champs que vous pouvez assigner en masse (Mass Assignable)
    protected $fillable = [
        'id',
        'title',
        'description',
        'status',
        'requested_by_user_id',
        'requested_by_connection_id',
        'assignable_id',
        'assignable_type',
        'region_id',
        'zone_id',
        'intervention_reason',
        'category',
        'technical_complexity',
        'min_time_hours',
        'max_time_hours',
        'comments',
        'priority',
        'scheduled_date',
        'completed_date',
        'resolution_notes',
        'gps_latitude',
        'gps_longitude',

    ];
    protected $casts = [
        'reported_at' => 'datetime',
        'closed_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    // --- RELATIONS ---

    /**
     * Obtient le modèle qui a créé la demande d'intervention (ex: User, Client).
     * Cette relation est maintenant plus spécifique grâce aux foreign keys.
     */
    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id', 'id');
    }

    public function requestedByConnection(): BelongsTo
    {
        return $this->belongsTo(Connection::class, 'requested_by_connection_id');
    }

    /**
     * Obtient le technicien assigné à la demande.
     */
    public function assignable()
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
     * Obtient la zone à laquelle la demande est associée.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
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

    public function activity(): HasOne
    {
        return $this->hasOne(Activity::class);
    }

    // Vous pourriez ajouter d'autres relations HasMany ici (ex: commentaires, logs d'activité)
    /*
    public function comments(): HasMany
    {
        return $this->hasMany(InterventionComment::class);
    }
    */
}

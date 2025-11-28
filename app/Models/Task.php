<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    /**
     * Les statuts possibles pour une tâche.
     */
    public const STATUS_PLANNED = 'Planifiée';
    public const STATUS_IN_PROGRESS = 'En cours';
    public const STATUS_COMPLETED = 'Terminée';
    public const STATUS_CANCELLED = 'Annulée';
    public const STATUS_LATE = 'En retard';
    public const STATUS_STARTED_LATE = 'Commencée en retard';

    /**
     * Les attributs qui peuvent être assignés en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'maintenance_type',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'time_spent', // En minutes
        'equipment_id',
        'user_id',
        'team_id',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'planned_start_date' => 'datetime',
        'planned_end_date' => 'datetime',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
    ];

    /**
     * Récupère l'utilisateur assigné à la tâche.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Récupère l'équipe assignée à la tâche.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Récupère l'équipement concerné par la tâche.
     */
    public function equipment(): BelongsTo
    {
        // Assurez-vous d'avoir un modèle Equipment
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Récupère les activités (ou commentaires/mises à jour) liées à la tâche.
     */
    public function activities(): HasMany
    {
        // Assurez-vous d'avoir un modèle Activity
        return $this->hasMany(Activity::class);
    }

    /**
     * Scope pour calculer le temps d'intervention moyen pour les tâches terminées.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAverageInterventionTime($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)->avg('time_spent');
    }

    /**
     * Accesseur pour calculer la durée réelle de l'intervention en minutes.
     *
     * @return int|null
     */
    public function getActualDurationAttribute(): ?int
    {
        if ($this->actual_start_date && $this->actual_end_date) {
            return $this->actual_start_date->diffInMinutes($this->actual_end_date);
        }
        return null;
    }

    /**
     * KPI: Indique si la tâche a été terminée en retard par rapport à la date planifiée.
     *
     * @return bool
     */
    public function isCompletedLate(): bool
    {
        return $this->status === self::STATUS_COMPLETED &&
               $this->actual_end_date &&
               $this->planned_end_date &&
               $this->actual_end_date->isAfter($this->planned_end_date);
    }
}

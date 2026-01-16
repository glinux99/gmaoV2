<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'assignable_type',
        'assignable_id',
        'status',
        'priority',
        'maintenance_type',
        'planned_start_date',
        'planned_end_date',
        'time_spent',
        'estimated_cost',
        'region_id',
        'jobber',
        'requester_department',
        'department',
        'requires_shutdown',
        'equipment_task'
        // 'node_instructions' n'est pas un champ de la table 'tasks',
        // il est géré via la relation, donc on le retire de $fillable.
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
     * Get the parent assignable model (User, Team, etc.).
     */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

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
     * Récupère la région associée à la tâche.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Récupère la maintenance à laquelle cette tâche est associée.
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    /**
     * Récupère l'équipement concerné par la tâche.
     */
      public function equipments(): BelongsToMany
    {
        // Supposons une table pivot 'equipment_task'
        return $this->belongsToMany(Equipment::class);
    }

    /**
     * Récupère les activités (ou commentaires/mises à jour) liées à la tâche.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
     public function activity(): HasMany
    {
        return $this->hasMany(Activity::class);
    }


    /**
     * Récupère les instructions associées à la tâche.
     */
    public function instructions(): HasMany
    {
        return $this->hasMany(TaskInstruction::class);
    }

    /**
     * Get the service orders for the task.
     */
    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
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
    public function spareParts()
{
    return $this->belongsToMany(SparePart::class)->withPivot('quantity_used')->withTimestamps();
}

}

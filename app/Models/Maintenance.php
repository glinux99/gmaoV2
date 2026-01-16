<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class Maintenance extends Model implements HasMedia
{
    use HasFactory,InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',

        // Relations Polymorphes et Clés Étrangères
        'assignable_type',
        'assignable_id',
        'region_id',
        'maintenance_schedule_id',
        'intervention_request_id',
        'network_node_id',
        'network_id',
        'monthly_recurrence_type',
        'recurrence_day',
        // Planification et Coût
        'type',
        'status',
        'priority',
        'scheduled_start_date',
        'scheduled_end_date',
        'estimated_duration',
        'started_at',
        'completed_at',
        'labor_cost',
        'material_cost',
        'cost',

        // Champs de récurrence (Ajoutés)
        'recurrence_type',
        'recurrence_interval',
        'recurrence_month_interval',
        'recurrence_days',
        'recurrence_day_of_month',
        'recurrence_month',
        'reminder_days',
        'custom_recurrence_config',
        'regenerated_dates'
    ];

    protected $casts = [
        'scheduled_start_date' => 'datetime',
        'scheduled_end_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'recurrence_days' => 'array', // Important: Caste le champ JSON en tableau PHP
    ];

    // --- RELATIONS ---

    /**
     * Get the parent assignable model (User, Team, etc.).
     */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assignable_id')->where('assignable_type', Team::class);
    }

    // Correction: Relation Many-to-Many pour les équipements (utilisée dans le Controller)
    public function equipments(): BelongsToMany
    {
        // Supposons une table pivot 'equipment_maintenance'
       return $this->belongsToMany(Equipment::class, 'equipment_maintenance')
                ->withPivot('network_node_id') // <--- CRUCIAL
                ->withTimestamps();
    }

    // Relation BelongsTo (Clés étrangères)

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    public function interventionRequest(): BelongsTo
    {
        return $this->belongsTo(InterventionRequest::class);
    }

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }

    // Relations HasMany (Entités liées)

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
 public function maintenaces(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }
    /**
     * Get the instructions for the maintenance.
     */
    public function instructions(): HasMany
    {
        return $this->hasMany(MaintenanceInstruction::class);
    }

    /**
     * Get the activity associated with the maintenance.
     */
    public function activity(): HasOne
    {
        return $this->hasOne(Activity::class);
    }

    /**
     * Get the status history for the maintenance.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(MaintenanceStatusHistory::class)->latest();
    }

    public function expenses(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Expenses::class, 'expensable');
    }
}

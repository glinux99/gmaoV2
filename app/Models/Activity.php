<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class Activity extends Model implements HasMedia
{
    use HasFactory,InteractsWithMedia;

    protected $fillable = [

        'task_id',
        'maintenance_id',
        'intervention_request_id',
        'user_id', // The user who created the activity record
        'actual_start_time',
        'parent_id', // ID de l'activité parente
        'actual_end_time',
        'assignable_type', // Ajouté pour la relation polymorphe
        'assignable_id', // Ajouté pour la relation polymorphe
        'jobber',
        'spare_parts_used',
        'spare_parts_returned',
        'status',
        'problem_resolution_description',
        'proposals',
           'additional_information',
        'equipment_id',
        'title'
    ];
        protected $casts = [
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',

    ];

    protected static function booted(): void
    {
        static::updated(function (Activity $activity) {
            if ($activity->isDirty('status')) {
                $newStatus = $activity->status;

                if ($activity->task_id) {
                    $activity->task->update(['status' => $newStatus]);
                    Log::info("Task {$activity->task_id} status updated to {$newStatus} due to activity {$activity->id} status change.");
                }

                if ($activity->maintenance_id) {
                    $activity->maintenance->update(['status' => $newStatus]);
                    Log::info("Maintenance {$activity->maintenance_id} status updated to {$newStatus} due to activity {$activity->id} status change.");
                }

                if ($activity->intervention_request_id) {
                    $activity->interventionRequest->update(['status' => $newStatus]);
                    Log::info("InterventionRequest {$activity->intervention_request_id} status updated to {$newStatus} due to activity {$activity->id} status change.");
                }
            }
        });
    }
   public function assignable(): MorphTo
    {
        return $this->morphTo();
    }
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
     public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }
    public function interventionRequest(): BelongsTo
    {
        return $this->belongsTo(InterventionRequest::class);
    }
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class);
    }
    /**
     * Get the instructions for the activity.
     */
    public function activityInstructions(): HasMany
    {
        return $this->hasMany(ActivityInstruction::class);
    }

    /**
     * Accessor for instructions.
     * If maintenance_id is null, it returns the task's instructions.
     * Otherwise, it returns the activity's own instructions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInstructionsAttribute()
    {
        if (is_null($this->maintenance_id) && $this->task) {
            return $this->task->instructions;
        }
        return $this->activityInstructions;
    }
    /**
     * Récupère l'activité parente (si c'est une sous-activité).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Activity::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get all of the instruction answers for the Activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function instructionAnswers(): HasMany
    {
        return $this->hasMany(InstructionAnswer::class);
    }

    public function expenses(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Expenses::class, 'expensable');
    }

    /**
     * Get the spare parts used in this activity.
     */
    public function sparePartsUsed(): BelongsToMany
    {
        return $this->belongsToMany(SparePart::class, 'spare_part_activities', 'activity_id', 'spare_part_id')
                    ->withPivot('quantity_used');
    }

    /**
     * Get the spare parts returned in this activity.
     */
    public function sparePartsReturned(): BelongsToMany
    {
        return $this->belongsToMany(SparePart::class, 'spare_part_activities', 'activity_id', 'spare_part_id')
                    ->wherePivot('type', 'returned')
                    ->withPivot('quantity_used');
    }

    public function sparePartActivities(): HasMany
    {
        return $this->hasMany(SparePartActivity::class);
    }


}

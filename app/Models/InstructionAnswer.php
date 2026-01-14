<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class InstructionAnswer extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'activity_id',
        'task_instruction_id',
        'maintenance_instruction_id', // Ajout de la clé étrangère
        'activity_instruction_id',

        'user_id',
        'value',
    ];

    /**
     * Get the activity that this answer belongs to.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the task instruction that this answer is for.
     */
    public function taskInstruction(): BelongsTo
    {
        return $this->belongsTo(TaskInstruction::class);
    }

    /**
     * Get the maintenance instruction that this answer is for.
     */
    public function maintenanceInstruction(): BelongsTo
    {
        return $this->belongsTo(MaintenanceInstruction::class);
    }
     public function activityInstruction(): BelongsTo
    {
        return $this->belongsTo(ActivityInstruction::class);
    }


    /**
     * Get the user who provided the answer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskInstructionValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_instruction_id',
        'value',
    ];

    /**
     * Get the instruction this value belongs to.
     */
    public function taskInstruction(): BelongsTo
    {
        return $this->belongsTo(TaskInstruction::class);
    }
}

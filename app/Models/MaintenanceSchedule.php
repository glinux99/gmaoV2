<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'equipment_id',
        'frequency',
        'day_of_week',
        'execution_time',
        'next_due_date',
        'is_active',
    ];

    protected $casts = [
        'next_due_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}

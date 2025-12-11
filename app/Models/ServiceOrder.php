<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ServiceOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'maintenance_id', // Added this line
        'supplier_id',
        'reference',
        'description',
        'status',
        'cost',
        'order_date',
        'expected_completion_date',
        'actual_completion_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cost' => 'decimal:2',
        'order_date' => 'date',
        'expected_completion_date' => 'date',
        'actual_completion_date' => 'date',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function expenses(): MorphMany
    {
        return $this->morphMany(Expenses::class, 'expensable');
    }

    // Added this relationship
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }
}

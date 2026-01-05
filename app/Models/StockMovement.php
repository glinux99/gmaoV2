<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'movable_id',
        'movable_type',
        'user_id',
        'type',
        'quantity',
        'source_region_id',
        'destination_region_id',
        'responsible_user_id',
        'intended_for_user_id',
        'parent_movement_id',
        'notes',
        'date'
    ];

    /**
     * Get the parent movable model (SparePart or Equipment).
     */
    public function movable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who initiated the stock movement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source region for the movement.
     */
    public function sourceRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'source_region_id');
    }

    /**
     * Get the destination region for the movement.
     */
    public function destinationRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'destination_region_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
    public function intendedForUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'intended_for_user_id');
    }
}

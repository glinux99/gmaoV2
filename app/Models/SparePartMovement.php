<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class SparePartMovement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'spare_part_id',
        'user_id',
        'type',
        'quantity',
        'location',
        'notes',
    ];

    /**
     * Get the spare part associated with the movement.
     */
    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    /**
     * Get the user who performed the movement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}

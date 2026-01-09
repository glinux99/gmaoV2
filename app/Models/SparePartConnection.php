<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparePartConnection extends Model
{
    use HasFactory;
        protected $fillable = [
        'connection_id',
        'spare_part_id',
        'quantity_used',
        'type', // 'used' or 'returned'
    ];

    /**
     * Get the activity that owns the SparePartActivity.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }
}

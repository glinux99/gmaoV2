<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparePartCharacteristic extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'spare_part_id',
        'label_characteristic_id',
        'value',
    ];

    /**
     * Get the spare part that owns the characteristic.
     */
    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    /**
     * Get the characteristic definition.
     */
    public function labelCharacteristic(): BelongsTo
    {
        return $this->belongsTo(LabelCharacteristic::class);
    }
}

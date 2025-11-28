<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelCharacteristic extends Model
{
    use HasFactory;

    protected $fillable = [
        'label_id',
        'name',
        'type',
        'is_required',
    ];

    /**
     * Get the label that owns the characteristic definition.
     */
    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }
}

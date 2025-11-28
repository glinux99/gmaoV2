<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quantity',
        'min_quantity',
        'location',
        'region_id',
        'unity_id',
        'user_id',
        'label_id',
        'reference',
    ];

    /**
     * Get the label that owns the spare part.
     */
    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }

    /**
     * Get the user that owns the spare part.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the characteristics for the spare part.
     */
    public function characteristics(): HasMany
    {
        return $this->hasMany(SparePartCharacteristic::class);
    }

    public function sparePartCharacteristics(): HasMany
    {
        return $this->hasMany(SparePartCharacteristic::class);
    }

    /**
     * Get the region that owns the spare part.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
     public function unity(): BelongsTo
    {
        return $this->belongsTo(Unity::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'nomenclature',
        'number',
        'region_id',
    ];

    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}

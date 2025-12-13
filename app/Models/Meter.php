<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'serial_number',
        'model',
        'manufacturer',
        'type',
        'status',
        'installation_date',
        'connection_id',
        'notes',
    ];

    /**
     * Get the connection that owns the meter.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }

    /**
     * Get the seals for the meter.
     */
    public function seals(): HasMany
    {
        return $this->hasMany(Seal::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'serial_number',
        'type',
        'status',
        'installation_date',
        'meter_id',
        'connection_id',
        'notes',
    ];

    /**
     * Get the meter that owns the seal.
     */
    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    /**
     * Get the connection that owns the seal.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }
}

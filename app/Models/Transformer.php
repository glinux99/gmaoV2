<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transformer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transformer_id',
        'uuid',
        'measured_at',
        'temperature_alarm',
        'pressure_alarm',
        'oil_level_alarm',
        'dmcr_alarm',
        'dmcr_trip',
        'load_percentage',
        'oil_temperature',
        'ambient_temperature',
        'equipment_id',
        'network_node_id',
        'status',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'measured_at' => 'datetime',
        'metadata' => 'array',
        'temperature_alarm' => 'boolean',
        'pressure_alarm' => 'boolean',
        'oil_level_alarm' => 'boolean',
        'dmcr_alarm' => 'boolean',
        'dmcr_trip' => 'boolean',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }
}

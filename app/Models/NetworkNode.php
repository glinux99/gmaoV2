<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_id',
        'equipment_id',
        'is_active',
        'status',
        'is_root',
            'region_id',
        'zone_id',
        'x',
        'y',
        'w',
        'h',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_root' => 'boolean',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
    public function equipment(): BelongsTo {
        return $this->belongsTo(Equipment::class);
    }
       public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function characteristics(): HasMany
    {
        return $this->hasMany(NetworkNodeEquipmentCharacterstic::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}

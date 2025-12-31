<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Network extends Model
{
    use HasFactory;
    // App\Models\Network.php

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'zoom_level',
        'grid_size',
        'is_active',
        'region_id',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(NetworkNode::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(NetworkConnection::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(NetworkLabel::class);
    }



public function getStatsAttribute() {
    return [
        'total_equipments' => $this->nodes()->count(),
        'active_nodes' => $this->nodes()->where('is_active', true)->count(),
        'total_power_kva' => $this->nodes()->join('equipment', 'equipment.id', '=', 'network_nodes.equipment_id')
                                          ->where('equipment.unit', 'kVA')
                                          ->sum('equipment.puissance'),
        'is_energized' => $this->nodes()->where('is_root', true)->where('is_active', true)->exists(),
    ];
}

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkNodeEquipmentCharacterstic extends Model
{
    use HasFactory;

    protected $table = 'node_equip_specs';

    protected $fillable = [
        'network_node_id',
        'equipment_id',
        'equipment_characteristic_id',
        'value',
        'date',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function equipmentCharacteristic(): BelongsTo
    {
        return $this->belongsTo(EquipmentCharacteristic::class);
    }
}

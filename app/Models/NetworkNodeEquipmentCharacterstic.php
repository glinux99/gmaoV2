<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkNodeEquipmentCharacterstic extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_node_id',
        'equipment_id',
        'equipment_characteristic_id',
        'value',
        'date',
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

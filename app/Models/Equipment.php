<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Ajouté pour la relation de maintenance
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class Equipment extends Model
{
    use HasFactory,InteractsWithMedia;

    protected $fillable = [
        'tag',
        'designation',
        'brand',
        'model',
        'serial_number',
        'status',
        'location',
        'purchase_date',
        'warranty_end_date',
        'equipment_type_id',
        'region_id',
        'user_id',
        'parent_id',
        'quantity',
        'label_id',
        'characteristics'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_end_date' => 'date',
        'characteristics'=> 'array'
    ];

    // --- RELATIONS BELONGS TO ---

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relation pour la hiérarchie parent
    public function parent(): BelongsTo
    {
        // Spécifie explicitement la clé étrangère
        return $this->belongsTo(Equipment::class, 'parent_id');
    }

    // --- RELATIONS HAS MANY ---

    // Relation pour la hiérarchie enfant
    public function children(): HasMany
    {
        return $this->hasMany(Equipment::class, 'parent_id');
    }

    public function characteristics(): HasMany
    {
        return $this->hasMany(EquipmentCharacteristic::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(EquipmentMovement::class);
    }

    // --- CORRECTION : RELATION MANY-TO-MANY ---

    /**
     * Get the maintenances associated with the equipment (Many-to-Many).
     */
    public function maintenances(): BelongsToMany
    {
        // Ceci suppose l'existence d'une table pivot 'equipment_maintenance'
        return $this->belongsToMany(Maintenance::class);
    }
 public function tasks(): BelongsToMany
    {
        // Ceci suppose l'existence d'une table pivot 'equipment_maintenance'
        return $this->belongsToMany(Task::class);
    }
    // Si la table pivot a un nom non conventionnel, utilisez:
    // public function maintenances(): BelongsToMany
    // {
    //     return $this->belongsToMany(Maintenance::class, 'nom_de_votre_table_pivot');
    // }
    // equipment.id -> equipment_maintenance.equipment_id
public function maintenanceRecords() { return $this->hasMany(MaintenanceRecord::class); }
}

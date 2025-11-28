<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
 protected $table = 'maintenance_records';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'equipment_id',
        'user_id',
        'description',
        'type',
        'status',
        'scheduled_date',
        'completion_date',
        'cost',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_date' => 'date',
        'completion_date' => 'date',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the equipment that this maintenance belongs to.
     */
   // equipment_maintenance.equipment_id -> equipment.id
public function equipment() { return $this->belongsTo(Equipment::class); }
// equipment_maintenance.user_id -> users.id
public function user() { return $this->belongsTo(User::class); }
}


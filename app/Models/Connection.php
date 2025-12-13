<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_code',
        'region_id',
        'zone_id',
        'status',
        'first_name',
        'last_name',
        'phone_number',
        'secondary_phone_number',
        'gps_latitude',
        'gps_longitude',
        'customer_type',
        'customer_type_details',
        'commercial_agent_name',
        'amount_paid',
        'payment_number',
        'payment_voucher_number',
        'payment_date',
        'is_verified',
        'connection_type',
        'connection_date',
        'meter_id',
        'keypad_id',
        'cable_section',
        'meter_type_connected',
        'cable_length',
        'box_type',
        'meter_seal_number',
        'box_seal_number',
        'phase_number',
        'amperage',
        'voltage',
        'with_ready_box',
        'tariff',
        'tariff_index',
        'pole_number',
        'distance_to_pole',
        'needs_small_pole',
        'bt_poles_installed',
        'small_poles_installed',
        'additional_meter_1',
        'additional_meter_2',
        'additional_meter_3',
        'rccm_number',
        'materials_used',
    ];

    protected $casts = ['materials_used' => 'array'];

    /**
     * Get the region that owns the connection.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the zone that owns the connection.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the meter associated with the connection.
     */
    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    /**
     * Get the keypad associated with the connection.
     */
    public function keypad(): BelongsTo
    {
        return $this->belongsTo(Keypad::class);
    }

    /**
     * Get the seals for the connection.
     */
    public function seals(): HasMany
    {
        return $this->hasMany(Seal::class);
    }

    /**
     * Get the additional meters for the connection.
     */
    public function additionalMeters(): HasMany
    {
        return $this->hasMany(Meter::class, 'connection_id')->whereNotNull('is_additional');
    }
}

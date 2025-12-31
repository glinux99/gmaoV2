<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstructionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'instructions', // Stocke les instructions sous forme de JSON
    ];

    protected $casts = [
        'instructions' => 'array', // Cast le champ 'instructions' en tableau PHP
    ];

    public function maintenances(): HasMany {
        return $this->hasMany(Maintenance::class);
    }
}

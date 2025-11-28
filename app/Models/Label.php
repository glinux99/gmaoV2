<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Label extends Model
{
    use HasFactory;

    protected $fillable = ['designation', 'descriptions', 'color'];

    /**
     * Obtenir les caractéristiques définies pour ce label.
     */
    public function characteristics(): HasMany
    {
        return $this->hasMany(LabelCharacteristic ::class);
    }
      public function labelCharacteristics(): HasMany
    {
        return $this->hasMany(LabelCharacteristic::class);
    }
}

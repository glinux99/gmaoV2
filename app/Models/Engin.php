<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Engin extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation',
        'type',
        'immatriculation',
        'date_mise_en_service',
        'etat',
        'description',
        'region_id',
    ];
    public function region()
{
    return $this->belongsTo(Region::class);
}

}

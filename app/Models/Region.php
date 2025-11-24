<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation',
        'type_centrale',
        'puissance_centrale',
    ];
    public function engins()
{
    return $this->hasMany(Engin::class);
}

}

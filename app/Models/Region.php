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
        'status',
        'puissance_centrale',
        'code'
    ];
    public function zones() {
        return $this->hasMany(Zone::class);
    }
    public function engins()
{
    return $this->hasMany(Engin::class);
}
    public function technicians()
{
    return $this->hasMany(User::class);
}
}

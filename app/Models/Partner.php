<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'logo', 'website', 'description', 'is_active', 'order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    /**
     * Accesseur pour obtenir l'URL complète du logo.
     */
    public function getLogoUrlAttribute()
    {
        return $this->logo ? Storage::disk('media')->url($this->logo) : null;
    }
}

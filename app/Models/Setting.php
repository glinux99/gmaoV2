<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Accesseur pour obtenir l'URL complète si la clé est un logo ou favicon.
     */
    public function getFileUrlAttribute()
    {
        if (in_array($this->key, ['logo_url', 'favicon_url']) && $this->value) {
            return Storage::disk('media')->url($this->value);
        }
        return null;
    }

    /**
     * Récupère la valeur d'un paramètre par sa clé.
     * Retourne null si la clé n'existe pas.
     */
    public static function get(string $key, $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Enregistre ou met à jour un paramètre.
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Supprime un paramètre.
     */
    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
    }
}

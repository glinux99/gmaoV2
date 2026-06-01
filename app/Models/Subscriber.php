<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'is_active',
        'subscribed_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'subscribed_at'  => 'datetime',
    ];

    // Campagnes auxquelles l'abonné est associé (mode custom)
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_subscriber');
    }

    // Emails envoyés à cet abonné
    public function sentEmails()
    {
        return $this->hasMany(SentEmail::class);
    }
}

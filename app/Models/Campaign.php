<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'content',
        'recipient_mode',
        'status',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    // Abonnés sélectionnés pour le mode custom
    public function subscribers()
    {
        return $this->belongsToMany(Subscriber::class, 'campaign_subscriber');
    }

    // Emails envoyés pour cette campagne
    public function sentEmails()
    {
        return $this->hasMany(SentEmail::class);
    }
}

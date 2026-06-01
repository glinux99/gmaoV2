<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'is_private', 'description', 'created_by'];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('last_read_at', 'is_muted')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getUnreadCountForUser($userId)
    {
        $lastRead = $this->users()->where('user_id', $userId)->first()->pivot->last_read_at ?? now();
        return $this->messages()->where('created_at', '>', $lastRead)->count();
    }
}

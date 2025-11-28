<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'team_leader_id',
    ];

    /**
     * Obtenir le chef d'équipe (un utilisateur).
     */
    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    /**
     * Obtenir les techniciens (membres) de l'équipe.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id')->using(TeamUser::class);
    }

}

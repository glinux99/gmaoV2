<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class Activity extends Model
{
    use HasFactory,InteractsWithMedia;

    protected $fillable = [

        'task_id',
        'user_id', // The user who created the activity record
        'actual_start_time',
        'parent_id', // ID de l'activité parente
        'actual_end_time',
        'assignable_type', // Ajouté pour la relation polymorphe
        'assignable_id', // Ajouté pour la relation polymorphe
        'jobber',
        'spare_parts_used',
        'spare_parts_returned',
        'status',
        'problem_resolution_description',
        'proposals',
        'instructions',
        'additional_information',
    ];
        protected $casts = [
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',

    ];
   public function assignable(): MorphTo
    {
        return $this->morphTo();
    }
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
    /**
     * Récupère l'activité parente (si c'est une sous-activité).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Activity::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

}

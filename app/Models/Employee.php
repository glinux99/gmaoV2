<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'date_of_birth',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'hire_date',
        'job_title',
        'department',
        'salary',
        'employment_status',
        'termination_date',
        'notes',
        'cv_path',
        'id_card_path',
        'contract_path',
        'other_documents',
    ];

    /**
     * Get the user that owns the employee record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function leaves(): HasMany {
        return $this->hasMany(Leave::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'status',
        'paid_by',
        'payable_type',
        'payable_id',
        'category',
    ];

    /**
     * Get the user who recorded the payment.
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get the parent payable model (e.g., Employee, Supplier).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}

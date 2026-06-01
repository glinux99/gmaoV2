<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'amount',
        'donation_type',
        'donation_date',
        'contacted',
        'tax_receipt',
        'newsletter',
        'notes',
        'order',
        'payment_method',
        'status',
        'transaction_id',
        'ip_address',
        'user_agent'
    ];
}

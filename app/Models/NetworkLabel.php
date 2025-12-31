<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class NetworkLabel extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_id',
        'text',
        'font_size',
        'color',
        'is_bold',
        'x',
        'y',
        'rotation'
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}

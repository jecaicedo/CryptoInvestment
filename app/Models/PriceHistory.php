<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    protected $fillable = [
        'cryptocurrency_id',
        'price',
        'percent_change_1h',
        'percent_change_24h',
        'percent_change_7d',
        'volume_24h',
        'market_cap',
        'recorded_at',
    ];

    protected $casts = ['recorded_at' => 'datetime'];

    public function cryptocurrency(): BelongsTo
    {
        return $this->belongsTo(Cryptocurrency::class);
    }
}
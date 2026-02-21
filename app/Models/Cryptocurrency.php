<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cryptocurrency extends Model
{
    protected $fillable = ['cmc_id', 'name', 'symbol', 'slug', 'is_tracked'];

    protected $casts = ['is_tracked' => 'boolean'];

    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }
}
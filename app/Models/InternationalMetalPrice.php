<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternationalMetalPrice extends Model
{
    protected $fillable = [
        'price_date',
        'gold_usd',
        'silver_usd',
        'platinum_usd',
        'palladium_usd',
    ];

    protected $casts = [
        'price_date' => 'datetime',
        'gold_usd' => 'decimal:2',
        'silver_usd' => 'decimal:2',
        'platinum_usd' => 'decimal:2',
        'palladium_usd' => 'decimal:2',
    ];

    /**
     * Get the latest international metal price
     */
    public static function getLatest(): ?self
    {
        return self::orderBy('price_date', 'desc')->first();
    }

    /**
     * Get international metal prices for a specific date range
     */
    public static function getRange(\DateTime $start, \DateTime $end): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereBetween('price_date', [$start, $end])
            ->orderBy('price_date', 'desc')
            ->get();
    }
}

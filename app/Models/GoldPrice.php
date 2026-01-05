<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldPrice extends Model
{
    protected $fillable = [
        'price_date',
        'p_pure',
        's_pure',
        'p_18k',
        's_18k',
        'p_14k',
        's_14k',
        'p_white',
        's_white',
        'p_silver',
        's_silver',
    ];

    protected $casts = [
        'price_date' => 'datetime',
    ];

    /**
     * Get the latest gold price
     */
    public static function getLatest(): ?self
    {
        return self::orderBy('price_date', 'desc')->first();
    }

    /**
     * Get gold prices for a specific date range
     */
    public static function getRange(\DateTime $start, \DateTime $end): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereBetween('price_date', [$start, $end])
            ->orderBy('price_date', 'desc')
            ->get();
    }
}

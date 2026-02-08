<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkGolfCourse extends Model
{
    protected $fillable = [
        'name',
        'region',
        'address',
        'area',
        'holes',
        'longitude',
        'latitude',
        'phone',
        'description',
        'detail_url',
    ];

    /**
     * 주어진 좌표로부터의 거리를 계산합니다 (km 단위)
     */
    public function distanceFrom(float $lat, float $lon): float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return PHP_FLOAT_MAX;
        }

        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat - $this->latitude);
        $lonDiff = deg2rad($lon - $this->longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

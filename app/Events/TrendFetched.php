<?php

namespace App\Events;

use App\Models\Trend;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrendFetched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Trend $trend 가져온 트렌드 데이터
     */
    public function __construct(
        public Trend $trend
    ) {
        //
    }
}

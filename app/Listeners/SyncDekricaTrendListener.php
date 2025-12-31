<?php

namespace App\Listeners;

use App\Events\TrendFetched;
use App\Jobs\SyncDekricaTrendData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncDekricaTrendListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TrendFetched $event): void
    {
        $trend = $event->trend;

        // Trend의 title을 태그로 사용하여 Dekrica API 호출
        $tag = $trend->title;

        Log::info('TrendFetched event - dispatching Dekrica sync', [
            'trend_id' => $trend->id,
            'tag' => $tag,
            'department_id' => $trend->department_id,
        ]);

        // Department ID를 Trend의 department_id로 사용
        SyncDekricaTrendData::dispatch($tag, $trend->department_id);
    }

    /**
     * Handle a job failure.
     */
    public function failed(TrendFetched $event, \Throwable $exception): void
    {
        Log::error('SyncDekricaTrendListener failed', [
            'trend_id' => $event->trend->id,
            'tag' => $event->trend->title,
            'error' => $exception->getMessage(),
        ]);
    }
}

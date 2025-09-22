<?php

namespace App\Domain\ogimage\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class OgImageKakaoFlushJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = "https://developers.kakao.com/tool/debugger/sharing";

        $output = shell_exec('node ' . base_path('node-scripts/flush-og.js') . ' ' . $url);

        Log::info($output);
    }
}

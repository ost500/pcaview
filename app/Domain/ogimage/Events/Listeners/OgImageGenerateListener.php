<?php

namespace App\Domain\ogimage\Events\Listeners;

use App\Domain\ogimage\Events\ContentsNewEvent;
use App\Domain\ogimage\Jobs\OgImageKakaoFlushJob;
use App\Domain\ogimage\OgImageGenerateService;
use Illuminate\Support\Facades\Log;

class OgImageGenerateListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        Log::info('OgImageGenerateListener');
    }

    /**
     * Handle the event.
     */
    public function handle(ContentsNewEvent $event): void
    {
        $ogImageGenerator = app(OgImageGenerateService::class);
        $ogImageGenerator->generate();

        dispatch(new OgImageKakaoFlushJob());
    }
}

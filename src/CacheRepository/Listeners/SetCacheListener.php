<?php

namespace Dovutuan\Laracom\CacheRepository\Listeners;

use Dovutuan\Laracom\CacheRepository\Events\SetCacheEvent;
use Dovutuan\Laracom\CacheRepository\Traits\CacheableRepository;
use Illuminate\Contracts\Queue\ShouldQueue;

class SetCacheListener implements ShouldQueue
{
    use CacheableRepository;

    /**
     * Handle the event.
     *
     * @param SetCacheEvent $event
     * @return void
     */
    public function handle(SetCacheEvent $event): void
    {
    }
}
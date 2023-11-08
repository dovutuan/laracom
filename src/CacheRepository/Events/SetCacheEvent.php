<?php

namespace Dovutuan\Laracom\CacheRepository\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class SetCacheEvent
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public readonly String $key, public readonly Model|array $data)
    {
    }
}
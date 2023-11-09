<?php

namespace Dovutuan\Laracom\DomRepository\Events;

readonly class AfterCreateEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public array $data)
    {
    }
}
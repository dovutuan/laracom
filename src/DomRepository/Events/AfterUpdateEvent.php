<?php

namespace Dovutuan\Laracom\DomRepository\Events;

readonly class AfterUpdateEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public array $dataBefore, public array $dataAfter)
    {
    }
}
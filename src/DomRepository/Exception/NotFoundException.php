<?php

namespace Dovutuan\Laracom\DomRepository\Exception;

use Exception;

class NotFoundException extends Exception
{
    protected $code = 404;
}
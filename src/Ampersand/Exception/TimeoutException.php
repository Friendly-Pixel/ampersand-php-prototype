<?php

namespace Ampersand\Exception;

use Ampersand\Exception\AmpersandException;

class TimeoutException extends AmpersandException
{
    protected int $httpCode = 400; // Bad request
}

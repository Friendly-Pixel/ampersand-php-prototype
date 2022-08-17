<?php

namespace Ampersand\Exception;

use Ampersand\Exception\AmpersandException;

class BadRequestException extends AmpersandException
{
    protected int $httpCode = 400;
}

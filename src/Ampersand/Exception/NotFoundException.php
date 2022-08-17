<?php

namespace Ampersand\Exception;

use Ampersand\Exception\AmpersandException;

class NotFoundException extends AmpersandException
{
    protected int $httpCode = 404; // Not found
}

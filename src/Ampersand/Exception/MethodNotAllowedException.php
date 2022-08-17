<?php

namespace Ampersand\Exception;

use Ampersand\Exception\AmpersandException;

class MethodNotAllowedException extends AmpersandException
{
    protected int $httpCode = 405; // Method not allowed
}

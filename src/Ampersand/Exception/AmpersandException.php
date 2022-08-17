<?php

namespace Ampersand\Exception;

use Ampersand\AmpersandApp;
use Ampersand\API\ErrorHandler\HttpExceptionInterface;
use Exception;

class AmpersandException extends Exception implements HttpExceptionInterface
{
    protected int $httpCode = 500;

    public function getHttpCode(AmpersandApp $app): int
    {
        return $this->httpCode;
    }

    public function getHttpMessage(AmpersandApp $app): string
    {
        return $this->getMessage();
    }

    public function getContextData(AmpersandApp $app, bool $displayErrorDetails): array
    {
        return [];
    }
}

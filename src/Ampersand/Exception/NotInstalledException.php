<?php

namespace Ampersand\Exception;

use Ampersand\AmpersandApp;
use Ampersand\Exception\AmpersandException;

class NotInstalledException extends AmpersandException
{
    protected int $httpCode = 500;

    public function getHttpMessage(AmpersandApp $app): string
    {
        return $this->getMessage() . ". Try reinstalling the application";
    }

    public function getContextData(AmpersandApp $app, bool $displayErrorDetails): array
    {
        return $displayErrorDetails ? ['navTo' => '/admin/installer'] : [];
    }
}

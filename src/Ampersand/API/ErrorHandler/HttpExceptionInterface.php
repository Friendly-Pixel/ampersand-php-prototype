<?php

namespace Ampersand\API\ErrorHandler;

use Ampersand\AmpersandApp;

interface HttpExceptionInterface
{
    public function getHttpCode(AmpersandApp $app): int;
    public function getHttpMessage(AmpersandApp $app): string;
    public function getContextData(AmpersandApp $app, bool $displayErrorDetails): array;
}

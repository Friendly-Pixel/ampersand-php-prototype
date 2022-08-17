<?php

namespace Ampersand\Exception;

use Ampersand\AmpersandApp;
use Ampersand\Exception\AmpersandException;

class SessionExpiredException extends AmpersandException
{
    protected int $httpCode = 401; // Unauthorized

    public function getContextData(AmpersandApp $app, bool $displayErrorDetails): array
    {
        if ($app->getSettings()->get('session.loginEnabled')) {
            return [
                'loginPage' => $app->getSettings()->get('session.loginPage'), // picked up by frontend to nav to login page
            ];
        }
        return [];
    }
}

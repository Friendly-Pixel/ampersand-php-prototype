<?php

namespace Ampersand\Exception;

use Ampersand\AmpersandApp;
use Ampersand\Exception\AmpersandException;

class AccessDeniedException extends AmpersandException
{
    public function getHttpCode(AmpersandApp $app): int
    {
        return $app->getSettings()->get('session.loginEnabled') 
                && !$app->getSession()->sessionUserLoggedIn() 
            ? 401 // Unauthorized
            : 403; // Forbidden
    }

    public function getHttpMessage(AmpersandApp $app): string
    {
        return $app->getSettings()->get('session.loginEnabled') 
                && !$app->getSession()->sessionUserLoggedIn() 
            ? 'Please login to access this page'
            : 'You do not have access to this page';
    }

    public function getContextData(AmpersandApp $app, bool $displayErrorDetails): array
    {
        return $app->getSettings()->get('session.loginEnabled') 
                && !$app->getSession()->sessionUserLoggedIn() 
            ? ['loginPage' => $app->getSettings()->get('session.loginPage')]
            : [];
    }
}

<?php

namespace Ampersand\Controller;

use Ampersand\Exception\AmpersandException;
use Ampersand\Exception\BadRequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LoginController extends AbstractController
{
    public function loginTest(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->preventProductionMode();

        if (!$this->app->getSettings()->get('session.loginEnabled')) {
            throw new AmpersandException("Testing login feature not applicable. Login functionality is not enabled");
        }

        if (!isset($args['accountId'])) {
            throw new BadRequestException("No account identifier 'accountId' provided");
        }

        $account = $this->app->getModel()->getConceptByLabel('Account')->makeAtom($args['accountId']);

        $this->app->login($account);

        return $this->success($response);
    }
}
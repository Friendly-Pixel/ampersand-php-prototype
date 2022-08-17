<?php

namespace Ampersand\Controller;

use Ampersand\Exception\AccessDeniedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExecEngineController extends AbstractController
{
    public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // Check for required role
        $allowedRoles = $this->app->getSettings()->get('rbac.adminRoles');
        if (!$this->app->hasRole($allowedRoles)) {
            throw new AccessDeniedException("You do not have access to run the exec engine");
        }
        
        $transaction = $this->app->newTransaction()->runExecEngine(true)->close();

        if ($transaction->isCommitted()) {
            $this->app->userLog()->notice("Run completed");
        } else {
            $this->app->userLog()->warning("Run completed but transaction not committed");
        }

        return $this->success($response);
    }
}

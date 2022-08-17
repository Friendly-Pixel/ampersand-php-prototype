<?php

namespace Ampersand\Controller;

use Ampersand\Exception\AccessDeniedException;
use Ampersand\Rule\RuleEngine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RuleEngineController extends AbstractController
{
    public function evaluateAllRules(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // Check for required role
        $allowedRoles = $this->app->getSettings()->get('rbac.adminRoles');
        if (!$this->app->hasRole($allowedRoles)) {
            throw new AccessDeniedException("You do not have access to evaluate all rules");
        }

        foreach ($this->app->getModel()->getAllConjuncts() as $conj) {
            /** @var \Ampersand\Rule\Conjunct $conj */
            $conj->evaluate()->persistCacheItem();
        }
        
        foreach (RuleEngine::getViolations($this->app->getModel()->getAllRules('invariant')) as $violation) {
            $this->app->userLog()->invariant($violation);
        }
        foreach (RuleEngine::getViolations($this->app->getModel()->getAllRules('signal')) as $violation) {
            $this->app->userLog()->signal($violation);
        }
        
        return $this->withJson(
            $this->app->userLog()->getAll(),
            200,
            $response
        );
    }
}

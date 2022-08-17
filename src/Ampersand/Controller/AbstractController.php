<?php

namespace Ampersand\Controller;

use Ampersand\AmpersandApp;
use Ampersand\Exception\AccessDeniedException;
use Ampersand\Frontend\FrontendInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractController
{
    protected ContainerInterface $container;

    protected AmpersandApp $app;

    protected FrontendInterface $frontend;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->app = $this->container->get('ampersand_app');
        $this->frontend = $this->app->frontend();
    }

    protected function withJson(mixed $data, int $code, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus($code);
    }

    protected function success(ResponseInterface $response): ResponseInterface
    {
        // Check all process rules that are relevant for the activate roles
        $this->app->checkProcessRules();

        return $this->withJson(
            $this->app->userLog()->getAll(), // Return all notifications
            200,
            $response
        );
    }

    protected function requireAdminRole(): void
    {
        // Access check
        if (!$this->app->hasRole($this->app->getSettings()->get('rbac.adminRoles'))) {
            throw new AccessDeniedException("You do not have admin role access");
        }
    }

    protected function preventProductionMode(): void
    {
        if ($this->app->getSettings()->get('global.productionEnv')) {
            throw new AccessDeniedException("Not allowed in production environment");
        }
    }
}

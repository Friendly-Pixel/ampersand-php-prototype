<?php

namespace Ampersand\Controller;

use Ampersand\Exception\AccessDeniedException;
use Ampersand\Exception\MetaModelException;
use Ampersand\Misc\Installer;
use Ampersand\Log\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class InstallerController extends AbstractController
{
    protected Installer $installer;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->installer = new Installer(Logger::getLogger('APPLICATION'));
    }

    protected function guard(): void
    {
        $allowedRoles = $this->app->getSettings()->get('rbac.adminRoles');
        if (!$this->app->hasRole($allowedRoles)) {
            throw new AccessDeniedException("You do not have access to run the installer");
        }
    }

    public function install(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // $this->guard(); // skip generic access control check

        $this->preventProductionMode(); // Reinstalling the whole application is not allowed in production environment
        
        $defaultPop = filter_var($request->getQueryParams()['defaultPop'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        $ignoreInvariantRules = filter_var($request->getQueryParams()['ignoreInvariantRules'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

        $this->app
            ->reinstall($defaultPop, $ignoreInvariantRules) // reinstall and initialize application
            ->setSession();
        
        return $this->success($response);
    }

    public function installMetaPopulation(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();

        $transaction = $this->app->newTransaction();

        $this->installer->reinstallMetaPopulation($this->app->getModel());
        
        $transaction->runExecEngine()->close(false, false);
        if ($transaction->isRolledBack()) {
            throw new MetaModelException("Meta population does not satisfy invariant rules. See log files");
        }

        return $this->success($response);
    }

    public function installNavmenu(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();

        $transaction = $this->app->newTransaction();

        $this->installer->reinstallNavigationMenus($this->app->getModel());

        $transaction->runExecEngine()->close(false, false);
        if ($transaction->isRolledBack()) {
            throw new MetaModelException("Nav menu population does not satisfy invariant rules. See log files");
        }

        return $this->success($response);
    }

    public function cleanupMetaPopulation(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();

        $trasaction = $this->app->newTransaction();

        $this->installer->cleanupMetaPopulation($this->app->getModel());

        $trasaction->runExecEngine()->close(dryRun: false, ignoreInvariantViolations: false);

        return $this->success($response);
    }

    public function updateChecksum(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();
        
        $this->app
            ->registerCurrentModelVersion()
            ->init()
            ->setSession();
        
        $this->app->userLog()->info('New model version registered. Checksum is updated');

        return $this->success($response);
    }
}

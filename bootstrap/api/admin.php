<?php

/** @phan-file-suppress PhanStaticCallToNonStatic */

use Ampersand\Controller\ExecEngineController;
use Ampersand\Controller\InstallerController;
use Ampersand\Controller\LoginController;
use Ampersand\Controller\PopulationController;
use Ampersand\Controller\ReportController;
use Ampersand\Controller\ResourceController;
use Ampersand\Controller\RuleEngineController;
use Ampersand\Controller\SessionController;
use Slim\Interfaces\RouteCollectorProxyInterface;

/**
 * @var \Slim\App $api
 */
global $api;

$api->group('/admin', function (RouteCollectorProxyInterface $group) {
    $group->get('/test/login/{accountId}', [LoginController::class, 'loginTest']);
    $group->get('/sessions/delete/expired', [SessionController::class, 'deleteExpiredSessions']);
    $group->post('/resource/{resourceType}/rename', [ResourceController::class, 'renameAtoms']);
    $group->get('/execengine/run', [ExecEngineController::class, 'run']);
    $group->get('/ruleengine/evaluate/all', [RuleEngineController::class, 'evaluateAllRules']);
    $group->post('/import', [PopulationController::class, 'importPopulationFromUpload']);
});

$api->group('/admin/installer', function (RouteCollectorProxyInterface $group) {
    $group->get('', [InstallerController::class, 'install'])->setName('applicationInstaller');
    $group->get('/metapopulation/install', [InstallerController::class, 'installMetaPopulation']);
    $group->get('/metapopulation/cleanup', [InstallerController::class, 'cleanupMetaPopulation']);
    $group->get('/navmenu', [InstallerController::class, 'installNavmenu']);
    $group->get('/checksum/update', [InstallerController::class, 'updateChecksum'])->setName('updateChecksum');
});

$api->group('/admin/report', function (RouteCollectorProxyInterface $group) {
    $group->get('/relations', [ReportController::class, 'reportRelations']);
    $group->get('/conjuncts/usage', [ReportController::class, 'conjunctUsage']);
    $group->get('/conjuncts/performance', [ReportController::class, 'conjunctPerformance']);
    $group->get('/interfaces', [ReportController::class, 'interfaces']);
    $group->get('/interfaces/issues', [ReportController::class, 'interfaceIssues']);
});

$api->group('/admin/exporter', function (RouteCollectorProxyInterface $group) {
    $group->get('/export/all', [PopulationController::class, 'exportAllPopulation']);
    $group->post('/export/selection', [PopulationController::class, 'exportSelectionOfPopulation']);
    $group->get('/export/metamodel', [ReportController::class, 'exportMetaModel']);
});

$api->group('/admin/utils', function (RouteCollectorProxyInterface $group) {
    $group->get('/regenerate-all-atom-ids[/{concept}]', [ResourceController::class, 'regenerateAtomIds']);
});

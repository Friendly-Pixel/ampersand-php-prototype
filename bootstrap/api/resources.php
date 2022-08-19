<?php

/** @phan-file-suppress PhanInvalidFQSENInCallable */

use Ampersand\Controller\ResourceController;
use Slim\Interfaces\RouteCollectorProxyInterface;

/**
 * @var \Slim\Slim $api
 */
global $api;

$api->group('/resource', function (RouteCollectorProxyInterface $group) {
    $group->get('', ResourceController::class . ':listResourceTypes');
    $group->get('/{resourceType}', ResourceController::class . ':getAllResourcesForType');
    $group->post('/{resourceType}', ResourceController::class . ':createNewResourceId');
    $group->get('/{resourceType}/{resourceId}[/{resourcePath:.*}]', ResourceController::class . ':getResource');
    $group->map(['PUT', 'PATCH', 'POST'], '/{resourceType}/{resourceId}[/{ifcPath:.*}]', ResourceController::class . ':putPatchPostResource');
    $group->delete('/{resourceType}/{resourceId}[/{ifcPath:.*}]', ResourceController::class . ':deleteResource');
});

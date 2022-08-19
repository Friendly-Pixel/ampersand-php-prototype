<?php

/** @phan-file-suppress PhanInvalidFQSENInCallable */

use Ampersand\Controller\SessionController;
use Slim\Interfaces\RouteCollectorProxyInterface;

/**
 * @var \Slim\Slim $api
 */
global $api;

$api->group('/app', function (RouteCollectorProxyInterface $group) {
    $group->patch('/roles', SessionController::class . ':updateRoles');
    $group->get('/navbar', SessionController::class . ':getNavMenu');
    $group->get('/notifications', SessionController::class . ':getNotifications');
});

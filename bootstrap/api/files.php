<?php

/** @phan-file-suppress PhanStaticCallToNonStatic */

use Ampersand\Controller\FileObjectController;
use Slim\Interfaces\RouteCollectorProxyInterface;

/**
 * @var \Slim\Slim $api
 */
global $api;

$api->group('/file', function (RouteCollectorProxyInterface $group) {
    $group->get('/{filePath:.*}', [FileObjectController::class, 'getFile']);
});

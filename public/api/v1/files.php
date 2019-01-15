<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Stream;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @var \Slim\Slim $api
 */
global $api;

/**************************************************************************************************
 *
 * resource calls WITHOUT interfaces
 *
 *************************************************************************************************/

/**
 * @phan-closure-scope \Slim\App
 */
$api->group('/file', function () {
    // Inside group closure, $this is bound to the instance of Slim\App
    /** @var \Slim\App $this */

    /**
     * @phan-closure-scope \Slim\Container
     */
    $this->get('/{fileName}', function (Request $request, Response $response, $args = []) {
        /** @var \Ampersand\AmpersandApp $ampersandApp */
        $ampersandApp = $this['ampersand_app'];

        $appAbsolutePath = $ampersandApp->getSettings()->get('global.absolutePath');
        $uploadFolder = $ampersandApp->getSettings()->get('global.uploadPath');
        $filePath = "{$appAbsolutePath}/{$uploadFolder}/{$args['fileName']}";
        $fs = new Filesystem;
        
        // Check if filePath exists (includes directories)
        if (!$fs->exists($filePath)) {
            throw new Exception("File not found", 404);
        }

        // Check if filePath is a file (and NOT a directory)
        if (!is_file($filePath)) {
            throw new Exception("File not found", 404);
        }

        $fileResource = fopen($filePath, 'rb');
        $stream = new Stream($fileResource); // create a stream instance for the response body

        return $response->withHeader('Content-Type', 'application/force-download')
                        ->withHeader('Content-Description', 'File Transfer')
                        ->withHeader('Content-Transfer-Encoding', 'binary')
                        ->withHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
                        ->withHeader('Pragma', 'public')
                        ->withBody($stream); // all stream contents will be sent to the response
    });
});

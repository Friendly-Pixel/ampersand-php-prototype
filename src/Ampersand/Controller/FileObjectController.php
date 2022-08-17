<?php

namespace Ampersand\Controller;

use Ampersand\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Stream;

class FileObjectController extends AbstractController
{
    public function getFile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $fs = $this->app->fileSystem();
        $filePath = $args['filePath'];
        
        // Check if filePath exists
        if (!$fs->has($filePath)) {
            throw new NotFoundException("File not found");
        }

        $fileResource = $fs->readStream($filePath);
        $stream = new Stream($fileResource); // create a stream instance for the response body
        $mimeType = $fs->getMimetype($filePath);
        if ($mimeType === false) {
            $mimeType = 'application/octet-stream'; // the "octet-stream" subtype is used to indicate that a body contains arbitrary binary data.
        }

        return $response->withHeader('Content-Description', 'File Transfer')
                        ->withHeader('Content-Type', $mimeType)
                        ->withHeader('Content-Transfer-Encoding', 'binary')
                        // ->withHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"') // enable this to force browser to download the file
                        ->withBody($stream); // all stream contents will be sent to the response
    }
}

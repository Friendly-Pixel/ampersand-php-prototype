<?php

namespace Ampersand\API\Handler;

use Ampersand\AmpersandApp;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

class MyErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        protected AmpersandApp $app,
        protected ResponseFactoryInterface $responseFactory,
        protected LoggerInterface $logger,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        return $response = $this->responseFactory->createResponse(500);
    }
}

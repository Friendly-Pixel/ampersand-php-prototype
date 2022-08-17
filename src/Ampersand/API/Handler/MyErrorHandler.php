<?php

namespace Ampersand\API\Handler;

use Ampersand\AmpersandApp;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

use function Ampersand\Misc\stackTrace;

class MyErrorHandler implements ErrorHandlerInterface
{
    protected int $code = 500;
    protected string $message = "An error occured. For more information see server log files";
    protected bool $displayErrorDetails = false;

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
        $this->displayErrorDetails = $displayErrorDetails;
        return $this->renderResponse($exception);
    }

    protected function renderResponse(Throwable $e, array $data = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($this->code)
            ->withHeader('Content-type', 'application/json');

        $body = [
            'error' => $this->code,
            'msg' => $this->message,
            'notifications' => $this->app->userLog()->getAll(),
            'html' => $this->displayErrorDetails ? stackTrace($e) : null,
            ...$data
        ];

        $response->getBody()->write(
            (string) json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $response;
    }
}

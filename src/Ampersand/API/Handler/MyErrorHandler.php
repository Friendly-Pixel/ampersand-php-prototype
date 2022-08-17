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
        $this->log($exception);
        return $this->renderResponse($exception);
    }

    protected function log(Throwable $e): void
    {
        if ($this->code >= 500) {
            $this->logger->error(stackTrace($e)); // For internal server errors we want the stacktrace to understand what's happening
        } else {
            $this->logger->notice($e->getMessage()); // For user errors a notice of the exception message is sufficient
        }
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

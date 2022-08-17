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
    protected Throwable $exception;
    protected int $code = 500;
    protected string $message = "An error occured. For more information see server log files";
    protected bool $displayErrorDetails = false;

    /**
     * Array for error context related data
     */
    protected array $data = [];

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
        $this->exception = $exception;
        $this->displayErrorDetails = $displayErrorDetails;
        $this->log();
        return $this->renderResponse($exception);
    }

    protected function getCode(): int
    {
        // Convert invalid HTTP status code to 500
        return $this->code < 100 || $this->code > 599 ? 500 : $this->code;
    }

    protected function log(): void
    {
        if ($this->getCode() >= 500) {
            $this->logger->error(stackTrace($this->exception)); // For internal server errors we want the stacktrace to understand what's happening
        } else {
            $this->logger->notice($this->exception->getMessage()); // For user errors a notice of the exception message is sufficient
        }
    }

    protected function renderResponse(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($this->getCode())
            ->withHeader('Content-type', 'application/json');

        $body = [
            'error' => $this->getCode(),
            'msg' => $this->message,
            'notifications' => $this->app->userLog()->getAll(),
            'html' => $this->displayErrorDetails ? stackTrace($this->exception) : null,
            ...$this->data
        ];

        $response->getBody()->write(
            (string) json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $response;
    }
}

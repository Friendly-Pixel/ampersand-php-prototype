<?php

namespace Ampersand\API\ErrorHandler;

use Ampersand\AmpersandApp;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

use function Ampersand\Misc\stackTrace;

class GenericErrorHandler implements ErrorHandlerInterface
{
    protected ServerRequestInterface $request;
    protected Throwable $exception;
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
        $this->request = $request;
        $this->exception = $exception;
        $this->displayErrorDetails = $displayErrorDetails;
        $this->log();
        return $this->renderResponse($exception);
    }

    protected function getCode(): int
    {
        return 500;
    }

    protected function getMessage(): string
    {
        return $this->exception->getMessage();
    }

    protected function getContextData(): array
    {
        return [];
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
        $response = $this->responseFactory->createResponse($this->validHttpResponseCode($this->getCode()))
            ->withHeader('Content-type', 'application/json');

        $body = [
            'error' => $this->getCode(),
            'msg' => $this->displayErrorDetails ? $this->getMessage() : "An error occured. For more information see server log files",
            'notifications' => $this->app->userLog()->getAll(),
            'html' => $this->displayErrorDetails ? stackTrace($this->exception) : null,
            ...$this->getContextData(),
        ];

        $response->getBody()->write(
            (string) json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $response;
    }

    protected function validHttpResponseCode(int $code): int
    {
        // Convert invalid HTTP status code to 500
        return $code < 100 || $code > 599 ? 500 : $code;
    }
}

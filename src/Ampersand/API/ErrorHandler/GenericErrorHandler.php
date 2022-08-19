<?php

namespace Ampersand\API\ErrorHandler;

use Ampersand\AmpersandApp;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpSpecializedException;
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
        return $this->renderResponse();
    }

    protected function getCode(): int
    {
        if ($this->exception instanceof HttpExceptionInterface) {
            return $this->exception->getHttpCode($this->app);
        }

        if ($this->exception instanceof HttpSpecializedException) {
            return $this->exception->getCode();
        }

        return 500;
    }

    protected function getMessage(): string
    {
        if ($this->exception instanceof HttpExceptionInterface) {
            return $this->exception->getHttpMessage($this->app);
        }

        if ($this->exception instanceof HttpSpecializedException) {
            return $this->exception->getDescription();
        }

        return $this->exception->getMessage();
    }

    protected function getUserMessage(): string
    {
        return match (true) {
            // If displayErrorDetails is set to true, the user may see the full details of the exception message
            $this->displayErrorDetails => $this->getMessage(),
            // If http code is defined AND not an internal server error, the user may see the full details
            // e.g. a 400 Bad Request exception can contain information for the user about what is wrong with the request
            $this->exception instanceof HttpExceptionInterface
                && $this->exception->getHttpCode($this->app) < 500 => $this->getMessage(),
            // Slim API framework http exceptions
            $this->exception instanceof HttpSpecializedException => $this->getMessage(),
            // Hide exception message from user
            default => "An error occured. For more information see server log files",
        }; 
    }

    protected function getContextData(): array
    {
        if ($this->exception instanceof HttpExceptionInterface) {
            return $this->exception->getContextData($this->app, $this->displayErrorDetails);
        }
        
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
            'msg' => $this->getUserMessage(),
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

<?php

namespace Ampersand\API\ErrorHandler;

class ApiNotFoundHandler extends GenericErrorHandler
{
    protected function getCode(): int
    {
        return 404;
    }

    protected function getMessage(): string
    {
        return "API path not found: {$this->request->getMethod()} {$this->request->getUri()}. Path is case sensitive";
    }
}

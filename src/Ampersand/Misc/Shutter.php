<?php

namespace Ampersand\Misc;

use Ampersand\Exception\TimeoutException;
use Ampersand\Log\Logger;
use Exception;
use Throwable;

class Shutter
{
    public function __construct(protected bool $debugMode = false)
    {}

    public function shutdown()
    {
        $lastError = error_get_last();

        if (is_null($lastError)) {
            return;
        }

        if ($this->isTimeout($lastError)) {
            $this->handleUncaughtException(new TimeoutException("Timeout"));
        }

        if ($this->isMemoryExhausted($lastError)) {
            $this->handleUncaughtException(new Exception("Memory exhausted", 500));
        }

        if (isset($lastError) && ($lastError['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
            $this->handleUncaughtException(new Exception($lastError['message'], 500));
        }

        Logger::getLogger('APPLICATION')->warning($lastError['message']);
    }

    public function handleUncaughtException(Throwable $exception)
    {
        Logger::getLogger('APPLICATION')->critical("Uncaught exception/error: '{$exception->getMessage()}' Stacktrace: {$exception->getTraceAsString()}");
        
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
        http_response_code(500);
        header("{$protocol} 500 Internal server error");
        header("Content-Type: application/json");
        print json_encode([
            'error' => $exception->getCode(),
            'msg' => $this->debugMode ? $exception->getMessage() : "An error occurred",
            'html' => $this->debugMode ? stackTrace($exception) : "See log for more information",
        ]);
        exit;
    }

    protected function isTimeout(array $lastError): bool
    {
        return str_starts_with($lastError['message'], 'Maximum execution time');
    }

    protected function isMemoryExhausted(array $lastError): bool
    {
        return str_starts_with($lastError['message'], 'Allowed memory size');
    }
}

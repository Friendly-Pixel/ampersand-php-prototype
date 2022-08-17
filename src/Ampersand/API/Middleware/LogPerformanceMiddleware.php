<?php

namespace Ampersand\API\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class LogPerformanceMiddleware implements MiddlewareInterface
{
    protected LoggerInterface $logger;
    protected string $prefix;
    protected ?float $startTime;

    public function __construct(LoggerInterface $logger, string $prefix = '', ?float $startTime = null)
    {
        $this->logger = $logger;
        $this->prefix = $prefix;
        $this->startTime = $startTime;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = $this->startTime ?? (float) microtime(true);

        $response = $handler->handle($request);

        // Report performance until here
        $executionTime = round(microtime(true) - $startTime, 2);
        $memoryUsage = round(memory_get_usage() / 1024 / 1024, 2); // Mb
        $this->logger->notice("{$this->prefix}Memory in use: {$memoryUsage} Mb");
        $this->logger->notice("{$this->prefix}Execution time: {$executionTime} sec");

        return $response;
    }
}

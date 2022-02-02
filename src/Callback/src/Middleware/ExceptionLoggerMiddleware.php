<?php

declare(strict_types=1);

namespace rollun\callback\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ExceptionLoggerMiddleware implements MiddlewareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            $this->logger->error('Unexpected callback exception.', [
                'exception' => $exception,
                'requestBody' => (string)$request->getBody(),
                'requestUri' => (string)$request->getUri()
            ]);
            throw $exception;
        }
    }
}
<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractParamsResolver implements MiddlewareInterface
{
    public const ATTRIBUTE_WEBHOOK_VALUE = "WebhookValue";

    public const HANDLE_METHOD = "";

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === static::HANDLE_METHOD) {
            $request = $this->resolveParams($request);
        }

        $response = $handler->handle($request);

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    abstract protected function resolveParams(ServerRequestInterface $request): ServerRequestInterface;
}

<?php


namespace rollun\callback\Middleware;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractParamsResolver implements MiddlewareInterface
{
    const ATTRIBUTE_WEBHOOK_VALUE = "WebhookValue";

    const HANDLE_METHOD = "";

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($request->getMethod() === static::HANDLE_METHOD) {
            $request = $this->resolveParams($request);
        }
        $response = $delegate->process($request);
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    abstract protected function resolveParams(ServerRequestInterface $request);
}
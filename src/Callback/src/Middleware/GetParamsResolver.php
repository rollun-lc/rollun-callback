<?php


namespace rollun\callback\Middleware;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class GetParamsResolver
 * @package rollun\callback\Middleware
 */
class GetParamsResolver extends AbstractParamsResolver
{
    const HANDLE_METHOD = "GET";

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function resolveParams(ServerRequestInterface $request)
    {
        $value = $request->getQueryParams();
        return $request->withAttribute(static::ATTRIBUTE_WEBHOOK_VALUE, $value);
    }
}
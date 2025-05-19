<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Middleware;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class GetParamsResolver
 * @package rollun\callback\Middleware
 */
class GetParamsResolver extends AbstractParamsResolver
{
    public const HANDLE_METHOD = "GET";

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function resolveParams(ServerRequestInterface $request): ServerRequestInterface
    {
        $value = $request->getQueryParams();

        return $request->withAttribute(static::ATTRIBUTE_WEBHOOK_VALUE, $value);
    }
}

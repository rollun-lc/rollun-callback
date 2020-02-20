<?php


namespace rollun\callback\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * TODO refactor, remove duplicate code with https://github.com/rollun-com/rollun-datastore/blob/master/src/DataStore/src/Middleware/ResourceResolver.php
 *
 * Extracts resource name and row id from URL or from request attributes
 *
 * Used request attributes:
 * - resourceName (data store service name)
 * - primaryKeyValue (primary key value to fetch record for record)
 *
 * Examples:
 *
 * - if URL is http://example.com/api/datastore/RESOURCE-NAME/ROW-ID
 *  $request->getAttribute('resourceName') returns 'RESOURCE-NAME'
 *  $request->getAttribute('primaryKeyValue') returns 'ROW-ID'
 *
 * - if URL is http://example.com/api/datastore/RESOURCE-NAME?eq(a,1)&limit(2,5)
 *  $request->getAttribute('resourceName') returns 'RESOURCE-NAME
 *  $request->getAttribute('primaryKeyValue') returns null
 *
 * Class ResourceResolver
 * @package rollun\datastore\Middleware
 */
class ResourceResolver implements MiddlewareInterface
{
    const BASE_PATH = '/api/webhook';

    const RESOURCE_NAME = 'resourceName';

    const PRIMARY_KEY_VALUE = 'primaryKeyValue';

    /**
     * @var string
     */
    protected $basePath;

    public function __construct($basePath = null)
    {
        $this->basePath = $basePath ?? self::BASE_PATH;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute(self::RESOURCE_NAME) !== null) {
            // Router have set "resourceName". It work in expressive.
            $id = empty($request->getAttribute("id")) ? null : $this->decodeString($request->getAttribute("id"));
            $request = $request->withAttribute(self::PRIMARY_KEY_VALUE, $id);
        } else {
            // "resourceName" isn't set. It work in stratigility.
            $path = $request->getUri()->getPath();
            $basePath = preg_quote(rtrim($this->basePath, '/'), '/');
            $pattern = "/{$basePath}\/([\w\~\-\_]+)([\/]([-%_A-Za-z0-9]+))?\/?$/";
            preg_match($pattern, $path, $matches);

            $resourceName = isset($matches[1]) ? $matches[1] : null;
            $request = $request->withAttribute(self::RESOURCE_NAME, $resourceName);

            $id = isset($matches[3]) ? $this->decodeString($matches[3]) : null;
            $request = $request->withAttribute(self::PRIMARY_KEY_VALUE, $id);
        }

        $response = $handler->handle($request);

        return $response;
    }

    private function decodeString($value)
    {
        return rawurldecode(
            strtr(
                $value,
                [
                    '%2D' => '-',
                    '%5F' => '_',
                    '%2E' => '.',
                    '%7E' => '~',
                ]
            )
        );
    }
}
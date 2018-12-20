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
use rollun\utils\Json\Serializer;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Create json http response
 *
 * Class JsonRenderer
 * @package rollun\datastore\Middleware
 */
class JsonRenderer implements MiddlewareInterface
{
    /**
     *  This constant specify key, which use to save response data
     */
    const RESPONSE_DATA = "responseData";

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = $request->getAttribute(static::RESPONSE_DATA);
        $data = !empty($data) ? $data : null;

        /** @var ResponseInterface $response */
        $response = $request->getAttribute(ResponseInterface::class) ?: null;

        if (!isset($response)) {
            $status = 200;
            $headers = [];
        } else {
            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
        }

        $stream =  new Stream('php://temp', 'wb+');
        $stream->write(Serializer::jsonSerialize($data));
        $stream->rewind();
        $response = new Response($stream, $status);

        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }
}

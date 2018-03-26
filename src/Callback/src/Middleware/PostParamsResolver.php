<?php


namespace rollun\callback\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use rollun\utils\Json\Serializer;

/**
 * Class PostParamsResolver
 * Try get interrupter value from post request
 * Use Content-Type header to resolve handle type
 *  - *application/json* -> try json parse
 *  - *multipart/form-data* -> get parsed body
 *  - *application/x-www-form-urlencoded* -> get parsed body
 *  - *else* -> request stream
 * @package rollun\callback\Middleware
 */
class PostParamsResolver extends AbstractParamsResolver
{
    const HANDLE_METHOD = "POST";

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function resolveParams(ServerRequestInterface $request)
    {
        $contentType = $request->getHeaderLine("Content-Type");
        switch ($contentType) {
            case preg_match('/application\/json/', $contentType):
                $value = Serializer::jsonUnserialize($request->getBody());
                break;
            case preg_match('/application\/x\-www\-form\-urlencoded/', $contentType):
            case preg_match('/multipart/form-data/', $contentType):
                $value = $request->getParsedBody();
                $files = $request->getUploadedFiles();
                if (is_array($value) && !empty($files)) {
                    $value = array_merge($value, ["files" => $files]);
                }
                break;
            default:
                $value = $request->getBody();
                break;
        }
        return $request->withAttribute(static::ATTRIBUTE_WEBHOOK_VALUE, $value);
    }
}
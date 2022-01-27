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
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
use rollun\utils\Json\Exception;
use rollun\utils\Json\Serializer;
use Laminas\Diactoros\Response\EmptyResponse;

class InterrupterMiddleware implements MiddlewareInterface
{
    const DEFAULT_ATTRIBUTE_NAME = 'resourceName';

    /**
     * @var string
     */
    protected $attributeName;

    /**
     * @var CallablePluginManager
     */
    protected $interrupterPluginManager;

    /**
     * InterrupterMiddleware constructor.
     * @param CallablePluginManager $interrupterPluginManager
     * @param string|null $attributeName
     */
    public function __construct(CallablePluginManager $interrupterPluginManager, string $attributeName = null)
    {
        $this->interrupterPluginManager = $interrupterPluginManager;
        $this->attributeName = $attributeName ?: self::DEFAULT_ATTRIBUTE_NAME;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $serviceName = $request->getAttribute($this->attributeName);

        if (!$this->interrupterPluginManager->has($serviceName)) {
            return new EmptyResponse(404);
        }

        try {
            $callable = $this->interrupterPluginManager->get($serviceName);
            $value = $request->getAttribute(AbstractParamsResolver::ATTRIBUTE_WEBHOOK_VALUE);
            $result = call_user_func($callable, $value);

            if ($result instanceof PayloadInterface) {
                $statusCode = 202;
            } elseif (is_array($result) && !empty($result['error'])) {
                $statusCode = 500;
            } else {
                $statusCode = 200;
            }

        } catch (\Throwable $t) {
            $result = ['error' => $t->getMessage()];

            $statusCode = 500;
        }

        $request = $request->withAttribute(JsonRenderer::RESPONSE_DATA, $result);
        $request = $request->withAttribute(ResponseInterface::class, new EmptyResponse($statusCode));
        $response = $handler->handle($request);

        return $response;
    }
}

<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\callback\Callback\Interrupter\InterrupterInterface;
use Zend\Diactoros\Response\EmptyResponse;

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
     * Fetch service name from request attribute and create interrupter
     *
     * @param ServerRequestInterface $request
     * @return callable
     */
    protected function getCallable(ServerRequestInterface $request): callable
    {
        $serviceName = $request->getAttribute($this->attributeName);

        if (!$serviceName) {
            throw new InvalidArgumentException('Undefined interrupter service');
        }

        return $this->interrupterPluginManager->get($serviceName);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $callable = $this->getCallable($request);
        $value = $request->getAttribute(AbstractParamsResolver::ATTRIBUTE_WEBHOOK_VALUE);

        try {
            $result = call_user_func($callable, $value);
            $request = $request->withAttribute(JsonRenderer::RESPONSE_DATA, $result);
            $statusCode = $callable instanceof InterrupterInterface ? 202 : 200;
        } catch (\Throwable $t) {
            $request = $request->withAttribute(JsonRenderer::RESPONSE_DATA, ['error' => $t->getMessage()]);
            $statusCode = 500;
        }

        $request = $request->withAttribute(ResponseInterface::class, new EmptyResponse($statusCode));
        $response = $handler->handle($request);

        return $response;
    }
}

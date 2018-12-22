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
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\Middleware\RequestHandlerMiddleware;
use Zend\Stratigility\MiddlewarePipe;

class WebhookMiddleware implements MiddlewareInterface
{
    protected $middlewarePipe;

    /**
     * WebhookMiddleware constructor.
     * @param InterrupterMiddleware $interrupterMiddleware
     * @param RequestHandlerInterface|null $renderer
     */
    public function __construct(InterrupterMiddleware $interrupterMiddleware, RequestHandlerInterface $renderer = null)
    {
        $this->middlewarePipe = new MiddlewarePipe();

        $this->middlewarePipe->pipe(new GetParamsResolver());
        $this->middlewarePipe->pipe(new PostParamsResolver());
        $this->middlewarePipe->pipe($interrupterMiddleware);

        if ($renderer) {
            $renderer = new RequestHandlerMiddleware($renderer);
        } else {
            $renderer = new JsonRenderer();
        }

        $this->middlewarePipe->pipe($renderer);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $this->middlewarePipe->process($request, $handler);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }
}

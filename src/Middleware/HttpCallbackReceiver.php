<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 30.12.16
 * Time: 3:10 PM
 */

namespace rollun\callback\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\callback\Callback\Interruptor\Job;
use rollun\callback\Callback\Interruptor\Process;
use rollun\callback\Callback\PromiserInterface;
use rollun\logger\Exception\LogExceptionLevel;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\MiddlewareInterface;

class HttpCallbackReceiver implements MiddlewareInterface
{

    /**
     * Process an incoming request and/or response.
     *
     * Accepts a server-side request and a response instance, and does
     * something with them.
     *
     * If the response is not complete and/or further processing would not
     * interfere with the work done in the middleware, or if the middleware
     * wants to delegate to another process, it can use the `$out` callable
     * if present.
     *
     * If the middleware does not return a value, execution of the current
     * request is considered complete, and the response instance provided will
     * be considered the response to return.
     *
     * Alternately, the middleware may return a response instance.
     *
     * Often, middleware will `return $out();`, with the assumption that a
     * later middleware will return a response.
     *
     * @param Request $request
     * @param Response $response
     * @param null|callable $out
     * @return null|Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $callback = $request->getBody()->getContents();

        $job = Job::unserializeBase64($callback);

        $callback = $job->getCallback();
        $value = $job->getValue();

        try {
            switch ($callback) {
                case $callback instanceof PromiserInterface:
                    call_user_func($callback, $value);
                    $data = $callback->getInterruptorResult();
                    break;
                case $callback instanceof InterruptorInterface:
                    $data = call_user_func($callback, $value);
                    break;
                case is_callable($callback):
                    $callback = new Process($callback);
                    $data = call_user_func($callback, $value);
                    break;
                default:
                    throw new CallbackException('Callback is not callable', LogExceptionLevel::CRITICAL);
            }


            return new JsonResponse([
                'data' => $data,
                'status' => 'complete',
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'data' => $e->getMessage()
            ], 500);
        }

    }
}
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
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\MiddlewareInterface;

class HttpInterruptorAction extends InterruptorAbstract
{

    /**
     * Call Interruptor or callback who sent in http body.
     * Read [HttpInterruptor](src\Callback\Interruptor\Http.php).
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
            /**
             * Different callback called differently
             */
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

            $request = $request->withAttribute('responseData', [
                'data' => $data,
                'status' => 'complete',
            ]);
            $response = new EmptyResponse(200);
        } catch (\Exception $e) {
            $request = $request->withAttribute('responseData', [
                'data' => $e->getMessage(),
                'status' => 'error',
            ]);
            $response = new EmptyResponse(500);
        }

        if (isset($out)) {
            return $out($request, $response);
        }

        return $response;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 30.12.16
 * Time: 3:10 PM
 */

namespace rollun\callback\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\callback\Callback\Interruptor\Job;
use rollun\callback\Callback\Interruptor\Process;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\MiddlewareInterface;

class HttpInterruptorAction extends InterruptorAbstract
{

    /**
     * Call Interruptor or callback who sent in http body.
     * Read [HttpInterruptor](src\Callback\Interruptor\Http.php).
     * @param Request $request
     * @param DelegateInterface $delegate
     * @return null|Response
     * @internal param Response $response
     * @internal param callable|null $out
     */
    public function process(Request $request, DelegateInterface $delegate)
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
                case $callback instanceof InterruptorInterface:
                    $data = call_user_func($callback, $value);
                    break;
                case is_callable($callback):
                    $callback = new Process($callback);
                    $data = call_user_func($callback, $value);
                    break;
                default:
                    throw new CallbackException('Callback is not callable');
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

        $response = $delegate->process($request);

        return $response;
    }

}
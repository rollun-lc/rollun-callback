<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 1:31 PM
 */

namespace rollun\callback\Middleware;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use Zend\Diactoros\Response\EmptyResponse;

class InterruptorCallerAction extends InterruptorAbstract
{

    /** @var  InterruptorInterface */
    protected $interruptor;

    /**
     * InterruptorAbstract constructor.
     * @param InterruptorInterface $interruptor
     */
    public function __construct(InterruptorInterface $interruptor)
    {
        $this->interruptor = $interruptor;
    }

    const KEY_INTERRUPTOR_VALUE = 'interruptorValue';
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
        $value = $request->getAttribute(static::KEY_INTERRUPTOR_VALUE);
        try {
            $result = call_user_func($this->interruptor, $value);
            $request = $request->withAttribute('responseData', $result);
            $response = new EmptyResponse(200);
        }catch (\Exception $e) {
            $request = $request->withAttribute('responseData', ['responseData', ['error' => $e->getMessage()]]);
            $response = new EmptyResponse(500);
        }

        $request = $request->withAttribute(Response::class, $response);

        if(isset($out)) {
            return $out($request,$response);
        }

        return $response;
    }
}
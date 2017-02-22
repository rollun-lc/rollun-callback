<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 1:31 PM
 */

namespace rollun\callback\Middleware;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use Zend\Diactoros\Response\EmptyResponse;

class InterruptorCallerAction extends InterruptorAbstract
{

    const KEY_INTERRUPTOR_VALUE = 'interruptorValue';

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

    /**
     * Call interrupt with value.
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
        } catch (Exception $e) {
            $request = $request->withAttribute('responseData', ['responseData', ['error' => $e->getMessage()]]);
            $response = new EmptyResponse(500);
        }

        $request = $request->withAttribute(Response::class, $response);

        if (isset($out)) {
            return $out($request, $response);
        }

        return $response;
    }
}
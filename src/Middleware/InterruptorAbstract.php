<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 1:17 PM
 */

namespace rollun\callback\Middleware;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\callback\Callback\Interruptor\InterruptorInterface;

abstract class InterruptorAbstract implements MiddlewareInterface
{
    abstract  public function process(Request $request, DelegateInterface $delegate);
}
<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 1:17 PM
 */

namespace rollun\callback\Middleware;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use Zend\Stratigility\MiddlewareInterface;

abstract class InterruptorAbstract implements MiddlewareInterface
{

    /**
     * Abstaract middleware for Interruptor handler
     * @param Request $request
     * @param Response $response
     * @param null|callable $out
     * @return null|Response
     */
    abstract public function __invoke(Request $request, Response $response, callable $out = null);
}
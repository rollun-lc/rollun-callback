<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 08.03.17
 * Time: 13:36
 */

namespace rollun\callback;

use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\actionrender\MiddlewareDeterminator\AttributeParam;
use rollun\callback\Middleware\Factory\ImplicitInterruptorMiddlewareAbstractFactory;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Class InterruptMiddlewareDeterminator
 * @package rollun\callback
 */
class InterruptMiddlewareDeterminator extends AttributeParam
{
    const INTERRUPT_MIDDLEWARE_PREFIX = ImplicitInterruptorMiddlewareAbstractFactory::INTERRUPT_MIDDLEWARE_PREFIX;

    /**
     * @param Request $request
     * @return string
     */
    public function getMiddlewareServiceName(Request $request)
    {
        $serviceName = parent::getMiddlewareServiceName($request);
        return $serviceName . static::INTERRUPT_MIDDLEWARE_PREFIX;
    }


}

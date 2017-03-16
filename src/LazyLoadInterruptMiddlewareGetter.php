<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 08.03.17
 * Time: 13:36
 */

namespace rollun\callback;

use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\actionrender\Interfaces\LazyLoadMiddlewareGetterInterface;
use rollun\actionrender\LazyLoadMiddlewareGetter\Attribute;
use rollun\callback\Middleware\Factory\InterruptorMiddlewareFactory;
use rollun\datastore\Middleware\Factory\DataStoreMiddlewareFactory;
use Zend\ServiceManager\Factory\FactoryInterface;

class LazyLoadInterruptMiddlewareGetter extends Attribute
{
    /**
     * @var string
     */
    protected $attributeName;


    public function __construct($attributeName = "resourceName")
    {
        parent::__construct($attributeName);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getLazyLoadMiddlewares(Request $request)
    {
        $serviceName = $request->getAttribute($this->attributeName);
        $result = [LazyLoadMiddlewareGetterInterface::KEY_FACTORY_CLASS => InterruptorMiddlewareFactory::class,
            LazyLoadMiddlewareGetterInterface::KEY_REQUEST_NAME => $serviceName,
            LazyLoadMiddlewareGetterInterface::KEY_OPTIONS => []];
        return [
            $result
        ];
    }
}

<?php

namespace rollun\callback\Callback\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\callback\Callback\Proxy;

class ProxyAbstractFactory extends HttpClientAbstractFactory
{
    public const DEFAULT_CLASS = Proxy::class;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $request = $container->get(ServerRequestInterface::class)();
        $instance = parent::__invoke($container, $requestedName, $options);
        $instance->setRequest($request);
        $instance->setMethod($request->getMethod());

        return $instance;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 1:14 PM
 */

namespace rollun\callback\Middleware\Factory;

use Interop\Container\ContainerInterface;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\callback\Callback\Interruptor\Process;
use rollun\callback\Middleware\InterruptorAbstract;
use rollun\callback\Middleware\InterruptorCallerAction;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImplicitInterruptorMiddlewareAbstractFactory implements AbstractFactoryInterface
{

    const INTERRUPT_MIDDLEWARE_PREFIX = "InterruptMiddleware";

    /**
     * Direct factory is factory who create middleware by $resourceName in path.
     * Create interruptor middleware by $resourceName in path.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $resourceName = $this->getResourceName($requestedName);
        $interruptMiddleware = null;
        $resource = $container->get($resourceName);
        switch (true) {
            case is_a($resource, InterruptorInterface::class, true):
                $interruptMiddleware = new InterruptorCallerAction($resource);
                break;
            case is_a($resource, InterruptorAbstract::class, true):
                $interruptMiddleware = $resource;
                break;
            case is_callable($resource):
                $interruptMiddleware = new InterruptorCallerAction(new Process($resource));
                break;
            default:
                if (!isset($interruptMiddleware)) {
                    throw new CallbackException(
                        'Can\'t make Middleware\InterruptorAbstract'
                        . ' for resource: ' . $resourceName
                    );
                }
        }
        return $interruptMiddleware;
    }

    /**
     * @param $requestedName
     * @return string
     */
    protected function getResourceName($requestedName) {
        if(preg_match('/^(?<resourceName>[\w\W]+)'.static::INTERRUPT_MIDDLEWARE_PREFIX.'/', $requestedName, $match)) {
            return $match["resourceName"];
        }
        return "";
    }

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $resourceName = $this->getResourceName($requestedName);
        if(empty($resourceName)) return false;
        return $container->has($resourceName);
    }
}
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
use Zend\ServiceManager\Factory\FactoryInterface;

class InterruptorMiddlewareFactory implements FactoryInterface
{

    /**
     * Direct factory is factory who create middleware by $resourceName in path.
     * Create interruptor middleware by $resourceName in path.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws CallbackException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $resourceName = $requestedName;
        if (!$container->has($resourceName)) {
            throw new CallbackException(
                'Can\'t make Middleware\InterruptorAbstract for resource: ' . $resourceName
            );
        }
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
                //todo: make util method for check instance closure for declare interface
                //check if closure is middleware
                /*$reflectionFunction = new \ReflectionFunction($resource);
                $reflectionMiddleware = new \ReflectionClass(MiddlewareInterface::class);
                $reflectionMethod = $reflectionMiddleware->getMethod("__invoke");
                $paramsActual = $reflectionFunction->getParameters();
                $paramsExist = $reflectionMethod->getParameters();
                if (count($paramsActual) == count($paramsExist)) {
                    for ($i = 0; $i < count($paramsActual); $i++) {
                        if(!$this->reflectionParamCompare($paramsActual[$i], $paramsExist[$i])) {
                            break;
                        }
                    }
                    $interruptMiddleware = $resource;
                }*/
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

    /*protected function reflectionParamCompare(\ReflectionParameter $parameter1, \ReflectionParameter $parameter2)
    {
        return (($parameter1->getType() == $parameter2->getType() ||
            $parameter1->getClass() == $parameter2->getClass()) &&
            $parameter1->getName() == $parameter2->getName()
        );
    }*/

}
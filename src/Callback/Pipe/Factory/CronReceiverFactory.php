<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.17
 * Time: 17:10
 */

namespace rollun\callback\Callback\Pipe\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Callback\Pipe\CronReceiver;
use rollun\callback\Middleware\CronReceiver as CronReceiverMiddleware;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class CronReceiverFactory implements FactoryInterface
{

    protected $middlewares;

    public function __construct($addMiddlewares = [])
    {
        $this->middlewares = $addMiddlewares;
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $this->middlewares[300] = new CronReceiverMiddleware();
        ksort($this->middlewares);
        return new CronReceiver($this->middlewares);
    }

    public function getMiddlewares()
    {
        return $this->middlewares;
    }
}
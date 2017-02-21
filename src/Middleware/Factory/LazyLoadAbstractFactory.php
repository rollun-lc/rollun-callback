<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 12:55 PM
 */

namespace rollun\callback\Middleware\Factory;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class LazyLoadAbstractFactory implements AbstractFactoryInterface
{

    const KEY_LAZY_LOAD = 'lazyLoad';

    const KEY_DIRECT_FACTORY = 'directFactory';

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        return isset($config[static::KEY_LAZY_LOAD][$requestedName]);
    }

    /**
     * Create middleware by DirectFactor.
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
        $config = $container->get('config');
        $factoryConfig = $config[static::KEY_LAZY_LOAD][$requestedName];
        if (!isset($factoryConfig[static::KEY_DIRECT_FACTORY]) ||
            empty($factoryConfig[static::KEY_DIRECT_FACTORY])
        ) {
           throw new ServiceNotCreatedException("Direct factory not set!");
        }

        $directFactory = new $factoryConfig[static::KEY_DIRECT_FACTORY]();
        $middlewareLazyLoad = function (
            Request $request,
            Response $response,
            $next = null
        ) use ($container, $directFactory) {
            $resourceName = $request->getAttribute('resourceName');
            $middleware = $directFactory($container, $resourceName);
            return $middleware($request, $response, $next);
        };

        return $middlewareLazyLoad;
    }
}
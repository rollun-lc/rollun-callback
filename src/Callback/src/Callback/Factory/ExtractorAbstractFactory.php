<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Factory;

use Interop\Container\ContainerInterface;
use rollun\callback\Callback\Extractor;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class ExtractorAbstractFactory implements AbstractFactoryInterface
{
    const KEY = 'keyQueueExtractor';

    const KEY_QUEUE_SERVICE_NAME = 'keyQueueServiceName';

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');

        return isset($config[static::KEY][$requestedName]);
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (empty($options)) {
            $config = $container->get('config');
            $serviceConfig = $config[static::KEY][$requestedName];
        } else {
            $serviceConfig = $options;
        }
        $queue = $container->get($serviceConfig[static::KEY_QUEUE_SERVICE_NAME]);
        $extractor = new Extractor($queue);

        return $extractor;
    }
}

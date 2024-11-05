<?php

namespace rollun\callback\PidKiller\Factory;

use Psr\Container\ContainerInterface;
use rollun\callback\PidKiller\WorkerProducer;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

class WorkerProducerAbstractFactory implements AbstractFactoryInterface
{

    public const IMPLICIT_PRODUCER_POSTFIX = '_Producer';

    /**
     * Can the factory create an instance for the service?
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $systemName = $this->getSystemName($requestedName);
        return ($systemName && $container->has($systemName));
    }

    private function getSystemName($requestedName)
    {
        //non strict return type 0 or false.
        if (false != preg_match('/(?<system>[\w\_\-]+)_Producer/', $requestedName, $match)) {
            return $match['system'];
        }
        return false;
    }

    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        try {
            $systemName = $this->getSystemName($requestedName);
            $queue = WorkerSystemAbstractFactory::buildQueue($container, $systemName, $container->get('config')[WorkerSystemAbstractFactory::class][$systemName]);
            return new WorkerProducer($queue);
        } catch (\Throwable $throwable) {
            throw new ServiceNotCreatedException(sprintf('Can\'t service %s. Reason: %s', $requestedName, $throwable->getMessage()), $throwable->getCode(), $throwable);
        }
    }
}

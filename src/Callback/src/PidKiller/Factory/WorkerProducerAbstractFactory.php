<?php


namespace rollun\callback\PidKiller\Factory;


use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\PidKiller\WorkerProducer;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

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
        if (false !== preg_match('/(<?system>[\w\_\-]+)_Producer/', $requestedName, $match)) {
            [$system] = $match;
            return $system;
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
     * @throws ContainerException if any other error occurs
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
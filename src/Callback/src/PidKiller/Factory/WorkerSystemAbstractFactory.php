<?php


namespace rollun\callback\PidKiller\Factory;


use Psr\Container\ContainerInterface;
use rollun\callback\Callback\Interrupter\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interrupter\InterrupterInterface;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\PidKiller\Worker;
use rollun\callback\PidKiller\WorkerManager;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;
use rollun\callback\Queues\Factory\SqsAdapterAbstractFactory;
use rollun\callback\Queues\QueueClient;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

class WorkerSystemAbstractFactory implements AbstractFactoryInterface
{


    /*
    - WORKER_MANAGER
        - WORKER
            - QUEUE
                - ADAPTER
            - PROCESS
            - WRITER
    - WORKER_PRODUCER
    */


    public const DEFAULT_TABLE_GATEWAY = 'slots';

    public const DEFAULT_PROCESS_COUNT = 1;

    public const DEFAULT_MAX_EXECUTE_TIME = null;

    public const DEFAULT_QUEUE_DELAY = 0;

    public const KEY_CLASS = 'class';

    public const DEFAULT_SQS_ADAPTER_MAX_RECEIVE_COUNT = 10;

    public const DEFAULT_SQS_ATTRIBUTES = [
        'VisibilityTimeout' => 10,
    ];

    /*
     * WorkerSystemAbstractFactory::class => [
     *  'proxyLoader' => [
     *      WorkerSystemAbstractFactory::KEY_CALLABLE => ProxyLoader:class,
     *      WorkerSystemAbstractFactory::KEY_WRITER => ProxyWriter:class,
     *  ],
     * ]
     */

    /**
     * @return AbstractFactoryInterface
     */
    public static function getWorkerSystemProducerAbstractFactory(): AbstractFactoryInterface
    {
        return new WorkerProducerAbstractFactory();
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
        $config = $options ?? $container->get('config')[self::class][$requestedName];
        return self::buildWorkerManager($container, $requestedName, $config);
    }

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return WorkerManager
     */
    protected static function buildWorkerManager(ContainerInterface $container, $requestedName, array $options = null): WorkerManager
    {
        $workerManagerName = sprintf('%s_WorkerManager', $requestedName);

        return (new WorkerManagerAbstractFactory())($container, $workerManagerName, [
            WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
            WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => $workerManagerName,
            WorkerManagerAbstractFactory::KEY_PROCESS =>
                $options[WorkerManagerAbstractFactory::KEY_PROCESS] ?? self::buildInterrupter(
                    $container,
                    $requestedName,
                    $options
                ),
            WorkerManagerAbstractFactory::KEY_PROCESS_COUNT =>
                $options[WorkerManagerAbstractFactory::KEY_PROCESS_COUNT] ?? self::DEFAULT_PROCESS_COUNT,
            WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY =>
                $options[WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY] ?? self::DEFAULT_TABLE_GATEWAY,
        ]);
    }

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return InterrupterInterface
     */
    protected static function buildInterrupter(ContainerInterface $container, $requestedName, array $options = null): InterrupterInterface
    {
        $interrupterName = sprintf('%s_Interrupter', $requestedName);
        //TODO: add build custom interrupter type.
        return (new ProcessAbstractFactory())($container, $interrupterName, [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME =>
                $options[ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME] ?? self::DEFAULT_MAX_EXECUTE_TIME,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE =>
                $options[ProcessAbstractFactory::KEY_CALLBACK_SERVICE] ?? self::buildWorker($container, $requestedName, $options)
        ]);
    }

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return Worker
     */
    protected static function buildWorker(ContainerInterface $container, $requestedName, array $options = null): Worker
    {
        $workerName = sprintf('%s_Worker', $requestedName);
        return (new WorkerAbstractFactory())($container, $workerName, [
            WorkerAbstractFactory::KEY_QUEUE => $options[WorkerAbstractFactory::KEY_QUEUE] ?? self::buildQueue($container, $requestedName, $options),
            WorkerAbstractFactory::KEY_INFO => $options[WorkerAbstractFactory::KEY_INFO] ?? null,
            WorkerAbstractFactory::KEY_CALLABLE => $options[WorkerAbstractFactory::KEY_CALLABLE],
            WorkerAbstractFactory::KEY_WRITER => $options[WorkerAbstractFactory::KEY_WRITER] ?? null,
        ]);
    }

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return QueueClient
     */
    public static function buildQueue(ContainerInterface $container, $requestedName, array $options = null): QueueClient
    {
        $queueName = sprintf('%s_Queue', $requestedName);
        return (new QueueClientAbstractFactory())($container, $queueName, [
            QueueClientAbstractFactory::KEY_NAME => $queueName,
            QueueClientAbstractFactory::KEY_DELAY => $options[QueueClientAbstractFactory::KEY_DELAY] ?? self::DEFAULT_QUEUE_DELAY,
            QueueClientAbstractFactory::KEY_ADAPTER => $options[QueueClientAbstractFactory::KEY_ADAPTER] ?? [
                    SqsAdapterAbstractFactory::KEY_MAX_RECEIVE_COUNT => self::DEFAULT_SQS_ADAPTER_MAX_RECEIVE_COUNT,
                    SqsAdapterAbstractFactory::KEY_SQS_ATTRIBUTES => self::DEFAULT_SQS_ATTRIBUTES,
                    SqsAdapterAbstractFactory::KEY_SQS_CLIENT_CONFIG => [
                        'key' => getenv('AWS_KEY'),
                        'secret' => getenv('AWS_SECRET'),
                        'region' => getenv('AWS_REGION'),
                    ],
                ],
        ]);
    }


    /**
     * Can the factory create an instance for the service?
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config')[self::class][$requestedName] ?? null;
        return $config !== null;
    }
}
<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller;

use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\InterrupterInterface;
use rollun\callback\Callback\Interrupter\Process;
use rollun\dic\InsideConstruct;
use Zend\Db\TableGateway\TableGateway;

class WorkerManager
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TableGateway
     */
    private $tableGateway;

    /**
     * @var Process
     */
    private $interrupter;

    /**
     * @var string
     */
    private $workerManagerName;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var int
     */
    private $processCount;

    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * WorkerManager constructor.
     * @param TableGateway $tableGateway
     * @param InterrupterInterface $interrupter
     * @param string $workerManagerName
     * @param int $processCount
     * @param ProcessManager|null $processManager
     * @param LoggerInterface|null $logger
     * @throws \ReflectionException
     */
    public function __construct(
        TableGateway $tableGateway,
        InterrupterInterface $interrupter,
        string $workerManagerName,
        int $processCount,
        ProcessManager $processManager = null,
        LoggerInterface $logger = null
    ) {
        InsideConstruct::setConstructParams(['logger' => LoggerInterface::class, 'processManager' => ProcessManager::class,]);
        $this->tableGateway = $tableGateway;
        $this->interrupter = $interrupter;
        $this->setWorkerManagerName($workerManagerName);
        $this->processCount = $processCount;
        $this->tableName = $tableGateway->getTable();
        $this->processManager = $processManager ?? new ProcessManager();
    }

    private function setWorkerManagerName($workerManagerName)
    {
        if (!$workerManagerName) {
            throw new \InvalidArgumentException('Worker manager name is invalid (empty)');
        }

        $this->workerManagerName = $workerManagerName;
    }


    public function __invoke()
    {
        $freeSlots = $this->setupSlots();

        if (!$freeSlots) {
            $this->logger->debug('All slots are in working');
        }

        $pids = [];

        foreach ($freeSlots as $freeSlot) {
            $pids[] = $this->refreshSlot($freeSlot);
        }

        return $pids;
    }

    private function refreshSlot($slot): ?int
    {
        $adapter = $this->tableGateway->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();

        try {
            $sql = 'SELECT `id`, `pid`'
                . " FROM {$adapter->getPlatform()->quoteIdentifier($this->tableGateway->getTable())}"
                . " WHERE {$adapter->getPlatform()->quoteIdentifier('id')} ="
                . " {$adapter->getPlatform()->quoteValue($slot['id'])}" . ' FOR UPDATE';

            $statement = $adapter->getDriver()->createStatement($sql);
            $statement->execute();
            $payload = $this->interrupter->__invoke();
            $info = $this->processManager->pidInfo($payload->getId());
            $this->tableGateway->update([
                'pid' => $payload->getId(),
                'pid_id' => $info['id']
            ], ['id' => $slot['id']]);
            $adapter->getDriver()->getConnection()->commit();

            $this->logger->debug("Update slot with pid = {$payload->getId()} where id = {$slot['id']}");
        } catch (\Throwable $e) {
            $adapter->getDriver()->getConnection()->rollback();
            $this->logger->error('Failed update slot', ['exception' => $e]);

            return null;
        }

        return (int)$payload->getId();
    }


    /**
     * Get array of killed processes
     *
     * @return array
     */
    private function setupSlots(): array
    {
        $slots = $this->tableGateway->select(['worker_manager' => $this->workerManagerName]);

        $freeSlots = $this->receiveFreeSlots($slots);
        if ($slots->count() < $this->processCount) {
            for ($i = $slots->count(); $i < $this->processCount; $i++) {
                $newSlot = [
                    'id' => uniqid($this->workerManagerName, true),
                    'pid' => '',
                    'pid_id' => '',
                    'worker_manager' => $this->workerManagerName,
                ];
                $this->tableGateway->insert($newSlot);
                $freeSlots[] = $newSlot;
            }
        } elseif ($slots->count() > $this->processCount) {
            for ($slot = current($freeSlots), $i = $slots->count(), $slotSkip = 0; $i > $this->processCount; $i--, $slot = next($freeSlots), $slotSkip++) {
                if (false !== $slot) {
                    $this->tableGateway->delete(['id' => $slot['id']]);
                } else {
                    //No free slot left.
                    return [];
                }
            }
            $freeSlots = array_slice($freeSlots, $slotSkip);
        }
        return $freeSlots;
    }

    /**
     * @param $slots
     * @return array
     */
    private function receiveFreeSlots($slots): array
    {
        $existingPids = $this->processManager->ps();
        $freeSlots = [];
        foreach ($slots as $slot) {
            $isSlotFree = true;

            foreach ($existingPids as $pidInfo) {
                if ($pidInfo['id'] === $slot['pid_id']) {
                    $isSlotFree = false;
                }
            }

            if ($isSlotFree) {
                $freeSlots[] = (array)$slot;
            }
        }

        return $freeSlots;
    }

    public function __sleep()
    {
        return ['interrupter', 'workerManagerName', 'processCount', 'tableName', 'processManager'];
    }

    public function __wakeup()
    {
        InsideConstruct::initWakeup([
            'logger' => LoggerInterface::class,
            'tableGateway' => $this->tableName,
        ]);
    }
}

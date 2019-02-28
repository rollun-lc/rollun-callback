<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller;

use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\Process;
use rollun\dic\InsideConstruct;
use Zend\Db\TableGateway\TableGateway;

class WorkerManager
{
    /**
     * @var TableGateway
     */
    protected $tableGateway;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @var string
     */
    private $workerManagerName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var int
     */
    protected $processCount;

    public function __construct(
        TableGateway $tableGateway,
        Process $process,
        string $workerManagerName,
        int $processCount,
        LoggerInterface $logger = null
    )
    {
        $this->tableGateway = $tableGateway;
        $this->process = $process;
        $this->setWorkerManagerName($workerManagerName);
        $this->processCount = $processCount;
        $this->tableName = $tableGateway->getTable();
        InsideConstruct::setConstructParams(['logger' => LoggerInterface::class]);

        $this->setUpSlots();
    }

    protected function setWorkerManagerName($workerManagerName)
    {
        if (!$workerManagerName) {
            throw new \InvalidArgumentException("Worker manager name is invalid (empty)");
        }

        $this->workerManagerName = $workerManagerName;
    }


    public function __invoke()
    {
        $freeSlots = $this->getFreeSlots();

        if (!$freeSlots) {
            $this->logger->debug("All slots are in working");
        }

        $pids = [];

        foreach ($freeSlots as $freeSlot) {
            $pids[] = $this->refreshSlot($freeSlot);
        }

        return $pids;
    }

    protected function refreshSlot($slot): ?int
    {
        $adapter = $this->tableGateway->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();

        try {
            $sql = "SELECT `id`, `pid`"
                . " FROM {$adapter->getPlatform()->quoteIdentifier($this->tableGateway->getTable())}"
                . " WHERE {$adapter->getPlatform()->quoteIdentifier('id')} ="
                . " {$adapter->getPlatform()->quoteValue($slot['id'])}" . " FOR UPDATE";

            $statement = $adapter->getDriver()->createStatement($sql);
            $statement->execute();
            $payload = $this->process->__invoke();
            $this->tableGateway->update([
                'pid' => $payload->getId(),
                'pid_id' => LinuxPidKiller::pidInfo($payload->getId())
            ], ['id' => $slot['id']]);
            $adapter->getDriver()->getConnection()->commit();

            $this->logger->debug("Update slot with pid = {$payload->getId()} where id = {$slot['id']}");
        } catch (\Throwable $e) {
            $adapter->getDriver()->getConnection()->rollback();
            $this->logger->error("Failed update slot", ['exception' => $e]);

            return null;
        }

        return intval($payload->getId());
    }

    protected function setUpSlots()
    {
        $this->tableGateway->delete(['worker_manager' => $this->workerManagerName]);
        $processCount = $this->processCount;

        while ($processCount) {
            $this->tableGateway->insert([
                'id' => uniqid($this->workerManagerName),
                'pid' => '',
                'pid_id' => '',
                'worker_manager' => $this->workerManagerName,
            ]);
            $processCount--;
        }
    }

    /**
     * Get array of killed processes
     *
     * @return array
     */
    protected function getFreeSlots(): array
    {
        $slots = $this->tableGateway->select(['worker_manager' => $this->workerManagerName]);
        $existingPids = LinuxPidKiller::ps();
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
        return ['process', 'workerManagerName', 'processCount', 'tableName'];
    }

    public function __wakeup()
    {
        InsideConstruct::initWakeup([
            'logger' => LoggerInterface::class,
            'tableGateway' => $this->tableName,
        ]);
    }
}

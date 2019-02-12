<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\Callback\PidKiller;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\PidKiller\LinuxPidKiller;
use rollun\callback\PidKiller\QueueClient;
use rollun\callback\PidKiller\WorkerManager;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;

class WorkerManagerTest extends TestCase
{
    /**
     * @var string
     */
    protected $tableName = 'slots';

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var TableGateway
     */
    protected $tableGateway;

    protected function setUp()
    {
        $this->adapter = new Adapter([
            'driver' => getenv('DB_DRIVER'),
            'database' => getenv('DB_NAME'),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASS'),
            'hostname' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT'),
        ]);

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (`id` VARCHAR(255), `pid` VARCHAR(255), `worker_manager` VARCHAR(255))";
        $statement = $this->adapter->query($sql);
        $statement->execute();
        $this->tableGateway = new TableGateway($this->tableName, $this->adapter);
    }

    protected function tearDown()
    {
        $statement = $this->adapter->query("DROP TABLE `{$this->tableName}`");
        $statement->execute();
    }

    protected function createPidQueue()
    {
        return new QueueClient(new FileAdapter('/tmp/test'), 'pidqueue');
    }

    public function testInvoke()
    {
        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());
        $process = new Process(function () {
            sleep(1000);
        }, $pidKiller);

        $workerManager = new WorkerManager($this->tableGateway, $process, 'test', 4);
        $pid = $workerManager->__invoke();
        $this->assertTrue($this->isProcessRunning($pid));
    }

    public function testInvokeWithRealRefresh()
    {
        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());
        $process = new Process(function () {
            sleep(2);
        }, $pidKiller);

        $workerManager = new WorkerManager($this->tableGateway, $process, 'test', 1);
        $pid1 = $workerManager->__invoke();
        $this->assertTrue($this->isProcessRunning($pid1));
        sleep(2);

        $pid2 = $workerManager->__invoke();
        $this->assertFalse($this->isProcessRunning($pid1));
        $this->assertTrue($this->isProcessRunning($pid2));

        sleep(2);
        $pid3 = $workerManager->__invoke();
        $this->assertFalse($this->isProcessRunning($pid1));
        $this->assertFalse($this->isProcessRunning($pid2));
        $this->assertTrue($this->isProcessRunning($pid3));
    }

    public function testSerialize()
    {
        $container = require 'config/container.php';
        $container->setService('slots', $this->tableGateway);

        // Init lifecycle token
        $lifeCycleToken = LifeCycleToken::generateToken();

        if (LifeCycleToken::getAllHeaders() && array_key_exists("LifeCycleToken", LifeCycleToken::getAllHeaders())) {
            $lifeCycleToken->unserialize(LifeCycleToken::getAllHeaders()["LifeCycleToken"]);
        }

        $container->setService(LifeCycleToken::class, $lifeCycleToken);

        InsideConstruct::setContainer($container);

        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());
        $process = new Process(function () {
            sleep(2);
        }, $pidKiller);

        $workerManager = new WorkerManager($this->tableGateway, $process, 'test', 1);
        $this->assertTrue(boolval(unserialize(serialize($workerManager))));
    }

    protected function isProcessRunning(int $pid): bool
    {
        $pids = LinuxPidKiller::ps();

        foreach ($pids as $pidInfo) {
            if ($pid == $pidInfo['pid']) {
                return true;
            }
        }

        return false;
    }
}

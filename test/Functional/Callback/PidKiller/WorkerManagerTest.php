<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Functional\Callback\PidKiller;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\PidKiller\LinuxPidKiller;
use rollun\callback\PidKiller\ProcessManager;
use rollun\callback\PidKiller\QueueClient;
use rollun\callback\PidKiller\WorkerManager;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;

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

    protected function setUp(): void
    {
        if (getenv("DB_DRIVER") === false) {
            $this->markTestIncomplete('Needs DB for running');
        }

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

    protected function tearDown(): void
    {
        $statement = $this->adapter->query("DROP TABLE `{$this->tableName}`");
        $statement->execute();
    }

    protected function createPidQueue()
    {
        return new QueueClient(new FileAdapter('/tmp/test'), 'pidqueue');
    }

    /*public function testInvoke()
    {
        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());
        $process = new Process(function () {
            sleep(5);
        }, $pidKiller);

        $workerManager = new WorkerManager($this->tableGateway, $process, 'test', 4);
        $pids = $workerManager->__invoke();

        foreach ($pids as $pid) {
            $this->assertTrue($this->isProcessRunning($pid));
        }
    }

    public function testInvokeWithLongProcess()
    {
        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());
        $process = new Process(function () {sleep(1000);}, $pidKiller);
        $workerManager = new WorkerManager($this->tableGateway, $process, 'test', 1);
        $pids = $workerManager->__invoke();

        foreach ($pids as $pid) {
            $this->assertTrue($this->isProcessRunning($pid));
        }

        $newPids = $workerManager->__invoke();

        foreach ($pids as $pid) {
            $this->assertTrue($this->isProcessRunning($pid));
        }

        $this->assertEquals(0, count($newPids));
    }

    public function testInvokeWithRealRefresh()
    {
        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());
        $process = new Process(function () {sleep(2);}, $pidKiller);

        $workerManager = new WorkerManager($this->tableGateway, $process, 'test', 4);
        $pids1 = $workerManager->__invoke();
        $this->assertEquals(count($pids1), 4);

        foreach ($pids1 as $pid) {
            $this->assertTrue($this->isProcessRunning($pid));
        }

        sleep(2);
        $pids2 = $workerManager->__invoke();
        $this->assertEquals(count($pids2), 4);

        foreach ($pids1 as $pid) {
            $this->assertFalse($this->isProcessRunning($pid));
        }
        foreach ($pids2 as $pid) {
            $this->assertTrue($this->isProcessRunning($pid));
        }

        sleep(2);
        $pids3 = $workerManager->__invoke();
        $this->assertEquals(count($pids3), 4);

        foreach ($pids1 as $pid) {
            $this->assertFalse($this->isProcessRunning($pid));
        }
        foreach ($pids2 as $pid) {
            $this->assertFalse($this->isProcessRunning($pid));
        }
        foreach ($pids3 as $pid) {
            $this->assertTrue($this->isProcessRunning($pid));
        }
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
    }*/

    /**
     * @todo delete
     */
    public function testTest()
    {
        $this->assertTrue(true);
    }

    protected function isProcessRunning(int $pid): bool
    {
        $pids = (new ProcessManager())->ps();

        foreach ($pids as $pidInfo) {
            if ($pid == $pidInfo['pid']) {
                return true;
            }
        }

        return false;
    }
}

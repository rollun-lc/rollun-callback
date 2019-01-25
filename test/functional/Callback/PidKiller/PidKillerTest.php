<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\Callback\PidKiller;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\PidKiller\LinuxPidKiller;
use rollun\callback\PidKiller\QueueClient as DelayedQueueClient;
use rollun\callback\Queues\QueueClient as SimpleQueueClient;
use rollun\callback\PidKiller\Worker;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\callback\Queues\Message;

class PidKillerTest extends TestCase
{
    protected $repository;

    protected $workQueue;

    protected $pidQueue;

    protected $worker;

    protected $pidKiller;

    protected function getWorkQueue()
    {
        if ($this->workQueue == null) {
            $this->workQueue = new SimpleQueueClient(new FileAdapter($this->repository), 'workqueue');
        }

        return $this->workQueue;
    }

    protected function getPidQueue()
    {
        if ($this->pidQueue == null) {
            $this->pidQueue = new DelayedQueueClient(new FileAdapter($this->repository), 'pidqueue');
        }

        return $this->pidQueue;
    }

    protected function getPidKiller($maxMessageCount = null)
    {
        if ($this->pidKiller == null) {
            $this->pidKiller = new LinuxPidKiller($this->getPidQueue(), $maxMessageCount);
        }

        return $this->pidKiller;
    }

    protected function createWorker($processLifetime, callable $callback)
    {
        return new Worker($this->getWorkQueue(), $this->getPidKiller(), new Process($callback), $processLifetime);
    }

    public function testPs()
    {
        $pids = LinuxPidKiller::ps();
        $this->assertEquals($pids[0]['pid'], 1);
    }

    public function testWorkflowWithoutDelayAndNotKill()
    {
        $worker = $this->createWorker(0, function ($value) {
            sleep(1000);
            echo $value;
        });

        $this->getWorkQueue()->addMessage(Message::createInstance('test1'));
        $this->getWorkQueue()->addMessage(Message::createInstance('test2'));

        $payload1 = $worker();
        $payload2 = $worker();

        $this->assertTrue($this->isProcessRunning($payload1->getId()));
        $this->assertTrue($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithoutDelayAndKill()
    {
        $pidKiller = $this->getPidKiller();
        $worker = $this->createWorker(0, function ($value) {
            sleep(1000);
            echo $value;
        });

        $this->getWorkQueue()->addMessage(Message::createInstance('test1'));
        $this->getWorkQueue()->addMessage(Message::createInstance('test2'));

        $payload1 = $worker();
        $payload2 = $worker();

        $pidKiller();

        $this->assertFalse($this->isProcessRunning($payload1->getId()));
        $this->assertFalse($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithDelayAndNotKill()
    {
        $worker = $this->createWorker(5, function ($value) {
            sleep(1000);
            echo $value;
        });

        $this->getWorkQueue()->addMessage(Message::createInstance('test1'));
        $this->getWorkQueue()->addMessage(Message::createInstance('test2'));

        $payload1 = $worker();
        $payload2 = $worker();

        sleep(5);

        $this->assertTrue($this->isProcessRunning($payload1->getId()));
        $this->assertTrue($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithDelayAndKill()
    {
        $pidKiller = $this->getPidKiller();
        $worker = $this->createWorker(5, function ($value) {
            sleep(1000);
            echo $value;
        });

        $this->getWorkQueue()->addMessage(Message::createInstance('test1'));
        $this->getWorkQueue()->addMessage(Message::createInstance('test2'));

        $payload1 = $worker();
        $payload2 = $worker();

        sleep(5);
        $pidKiller();

        $this->assertFalse($this->isProcessRunning($payload1->getId()));
        $this->assertFalse($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithRunPidKillerTooEarly()
    {
        $pidKiller = $this->getPidKiller();
        $worker = $this->createWorker(5, function ($value) {
            sleep(1000);
            echo $value;
        });

        $this->getWorkQueue()->addMessage(Message::createInstance('test1'));
        $this->getWorkQueue()->addMessage(Message::createInstance('test2'));

        $payload1 = $worker();
        $payload2 = $worker();

        $pidKiller();

        $this->assertTrue($this->isProcessRunning($payload1->getId()));
        $this->assertTrue($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithRunFewTimes()
    {
        $pidKiller = $this->getPidKiller(2);
        $worker = $this->createWorker(0, function ($value) {
            sleep(1000);
            echo $value;
        });

        $this->getWorkQueue()->addMessage(Message::createInstance('test1'));
        $this->getWorkQueue()->addMessage(Message::createInstance('test2'));
        $this->getWorkQueue()->addMessage(Message::createInstance('test3'));

        $worker();
        $worker();
        $worker();

        $pidKiller();
        $this->assertFalse($this->getPidQueue()->isEmpty());

        $pidKiller();
        $this->assertTrue($this->getPidQueue()->isEmpty());
    }

    public function setUp()
    {
        $this->repository = rtrim(getenv('FILE_ADAPTER_REPOSITORY'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!file_exists($this->repository)) {
            mkdir($this->repository);
        }
    }

    public function tearDown()
    {
        $this->rrmdir($this->repository);
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

    protected function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object))
                        $this->rrmdir($dir."/".$object);
                    else
                        unlink($dir."/".$object);
                }
            }
            rmdir($dir);
        }
    }
}

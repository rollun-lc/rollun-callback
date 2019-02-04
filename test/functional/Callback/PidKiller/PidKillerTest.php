<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\Callback\PidKiller;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\PidKiller\LinuxPidKiller;
use rollun\callback\PidKiller\PidKillerInterface;
use rollun\callback\PidKiller\QueueClient as DelayedQueueClient;
use rollun\callback\PidKiller\QueueClient;
use rollun\callback\Queues\QueueClient as SimpleQueueClient;
use rollun\callback\PidKiller\Worker;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueInterface;

class PidKillerTest extends TestCase
{
    protected $repository;

    protected $container;

    protected function getContainer(): ContainerInterface
    {
        if ($this->container == null) {
            $this->container = require 'config/container.php';
        }

        return $this->container;
    }

    protected function createPidQueue()
    {
        return new QueueClient(new FileAdapter('/tmp/test'), 'pidqueue');
    }

    protected function createProcess($callback)
    {
        return new Process($callback, null, null);
    }

    public function testPs()
    {
        $pids = LinuxPidKiller::ps();
        $this->assertEquals($pids[0]['pid'], 1);
    }

    public function testWorkflowWithoutDelayAndNotKill()
    {
        $payload1 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $payload2 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $this->assertTrue($this->isProcessRunning($payload1->getId()));
        $this->assertTrue($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithoutDelayAndKill()
    {
        /** @var PidKillerInterface $pidKiller */
        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());

        $payload1 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $payload2 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $pidKiller->create([
            'pid' => $payload1->getId(),
            'delaySeconds' => 0,
        ]);

        $pidKiller->create([
            'pid' => $payload2->getId(),
            'delaySeconds' => 0,
        ]);

        $pidKiller();

        $this->assertFalse($this->isProcessRunning($payload1->getId()));
        $this->assertFalse($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithDelayAndNotKill()
    {
        /** @var PidKillerInterface $pidKiller */
        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());

        $payload1 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $payload2 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $pidKiller->create([
            'pid' => $payload1->getId(),
            'delaySeconds' => 5,
        ]);

        $pidKiller->create([
            'pid' => $payload2->getId(),
            'delaySeconds' => 5,
        ]);

        sleep(5);

        $this->assertTrue($this->isProcessRunning($payload1->getId()));
        $this->assertTrue($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithDelayAndKill()
    {
        /** @var PidKillerInterface $pidKiller */
        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());

        $payload1 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $payload2 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $pidKiller->create([
            'pid' => $payload1->getId(),
            'delaySeconds' => 5,
        ]);

        $pidKiller->create([
            'pid' => $payload2->getId(),
            'delaySeconds' => 5,
        ]);

        sleep(5);
        $pidKiller();

        $this->assertFalse($this->isProcessRunning($payload1->getId()));
        $this->assertFalse($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithRunPidKillerTooEarly()
    {
        /** @var PidKillerInterface $pidKiller */
        $pidKiller = new LinuxPidKiller(null, $this->createPidQueue());

        $payload1 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $payload2 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $pidKiller->create([
            'pid' => $payload1->getId(),
            'delaySeconds' => 5,
        ]);

        $pidKiller->create([
            'pid' => $payload2->getId(),
            'delaySeconds' => 5,
        ]);

        $pidKiller();

        $this->assertTrue($this->isProcessRunning($payload1->getId()));
        $this->assertTrue($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithRunFewTimes()
    {
        $pidKiller = new LinuxPidKiller(2, $this->createPidQueue());

        $payload1 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $payload2 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $payload3 = $this->createProcess(function () {
            sleep(1000);
        })->__invoke();

        $pidKiller->create([
            'pid' => $payload1->getId(),
            'delaySeconds' => 2,
        ]);

        $pidKiller->create([
            'pid' => $payload2->getId(),
            'delaySeconds' => 2,
        ]);

        $pidKiller->create([
            'pid' => $payload3->getId(),
            'delaySeconds' => 2,
        ]);

        sleep(3);

        $pidKiller();
        $this->assertFalse($pidKiller->getPidQueue()->isEmpty());

        $pidKiller();
        $this->assertTrue($pidKiller->getPidQueue()->isEmpty());
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

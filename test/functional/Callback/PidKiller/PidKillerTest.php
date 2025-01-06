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
use rollun\callback\PidKiller\ProcessManager;
use rollun\callback\PidKiller\QueueClient;
use rollun\callback\Queues\Adapter\FileAdapter;

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

    protected function getProcessManager(): ProcessManager
    {
        static $processManager;

        if ($processManager === null) {
            $processManager = $this->getContainer()->get(ProcessManager::class);
        }
        return $processManager;
    }

    protected function createPidQueue()
    {
        return new QueueClient(new FileAdapter('/tmp/test'), 'pidqueue');
    }

    protected function createProcess($callback)
    {
        return new Process($callback, null, null);
    }

    public function testWorkflowWithoutDelayAndNotKill()
    {
        $payload1 = $this->createProcess(function () {
            sleep(120000);
        })->__invoke();

        $payload2 = $this->createProcess(function () {
            sleep(120000);
        })->__invoke();

        $this->assertTrue($this->isProcessRunning($payload1->getId()));
        $this->assertTrue($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithoutDelayAndKill()
    {
        $pidKiller = new LinuxPidKiller(null, $this->getProcessManager(), $this->createPidQueue());

        $payload1 = $this->createProcess(function () {
            sleep(12000);
        })->__invoke();

        $payload2 = $this->createProcess(function () {
            sleep(12000);
        })->__invoke();

        $pidKiller->create([
            'pid' => $payload1->getId(),
            'delaySeconds' => 0,
            'info' => $payload1
        ]);

        $pidKiller->create([
            'pid' => $payload2->getId(),
            'delaySeconds' => 0,
            'info' => $payload2
        ]);

        $pidKiller();

        $this->assertFalse($this->isProcessRunning($payload1->getId()));
        $this->assertFalse($this->isProcessRunning($payload2->getId()));
    }

    public function testWorkflowWithDelayAndNotKill()
    {
        $pidKiller = new LinuxPidKiller(null, $this->getProcessManager(), $this->createPidQueue());

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
        $pidKiller = new LinuxPidKiller(null, $this->getProcessManager(), $this->createPidQueue());

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
        $pidKiller = new LinuxPidKiller(null, $this->getProcessManager(), $this->createPidQueue());

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
        $pidKiller = new LinuxPidKiller(2, $this->getProcessManager(), $this->createPidQueue());

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
        sleep(1);
        $this->assertFalse($pidKiller->getPidQueue()->isEmpty());

        $pidKiller();
        sleep(1);
        $this->assertTrue($pidKiller->getPidQueue()->isEmpty());
    }

    protected function isProcessRunning(int $pid): bool
    {
        $pids = $this->getProcessManager()->ps();

        foreach ($pids as $pidInfo) {
            if ($pid == $pidInfo['pid']) {
                return true;
            }
        }

        return false;
    }

}

<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback\Queues\Adapter;

use PHPUnit\Framework\TestCase;
use rollun\callback\Queues\Adapter\FileAdapter;

class FileAdapterTest extends TestCase
{
    protected $repository;

    protected function createObject($timeInFlight): FileAdapter
    {
        return new FileAdapter($this->repository, $timeInFlight);
    }

    protected function setUp(): void
    {
        $dir = getenv('FILE_ADAPTER_REPOSITORY') === false ?
            (sys_get_temp_dir() . '/file-adapter-test/') : getenv('FILE_ADAPTER_REPOSITORY');
        $this->repository = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;


        if (!file_exists($this->repository)) {
            mkdir($this->repository);
        }
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->repository);
    }

    public function testCreateQueue()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $this->assertTrue(file_exists("{$this->repository}5a.queue"));
    }

    public function testCreteSameQueueSuccess()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $this->assertTrue(file_exists("{$this->repository}5a.queue"));

        $object = $this->createObject(10);
        $object->createQueue('a');
        $this->assertTrue(file_exists("{$this->repository}10a.queue"));
    }

    public function testCreteSameQueueFailed()
    {
        $this->expectException(\Exception::class);
        $object = $this->createObject(5);
        $object->createQueue('a');
        $this->assertTrue(file_exists("{$this->repository}5a.queue"));

        $object = $this->createObject(5);
        $object->createQueue('a');
    }

    public function testDelaySeconds()
    {
        $object = $this->createObject(10);
        $object->createQueue('a');
        $object->addMessage('a', 'message', null, 2);

        $this->assertTrue(empty($object->getMessages('a')));
        sleep(2);
        $this->assertTrue(!empty($object->getMessages('a')));
    }

    public function testTimeInFlight()
    {
        $object = $this->createObject(2);
        $object->createQueue('a');
        $object->addMessage('a', 'message');

        $this->assertTrue(!empty($object->getMessages('a')));
        sleep(3);
        $this->assertTrue(!empty($object->getMessages('a')));
    }

    public function testTimeInFlightWithDelete()
    {
        $object = $this->createObject(2);
        $object->createQueue('a');
        $object->addMessage('a', 'message');

        $message = $object->getMessages('a');
        $object->deleteMessage('a', $message[0]);
        sleep(3);
        $this->assertTrue(empty($object->getMessages('a')));
    }

    public function testGetMessageAndDelete()
    {
        $object = $this->createObject(null);
        $object->createQueue('a');
        $object->addMessage('a', 'message');
        $object->getMessages('a');

        $this->assertTrue($object->isEmpty('a'));
    }

    public function testGetMessageAndNotDelete()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $object->addMessage('a', 'message');
        $object->getMessages('a');

        $this->assertTrue(!$object->isEmpty('a'));
        $this->assertTrue(empty($object->getMessages('a')));
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

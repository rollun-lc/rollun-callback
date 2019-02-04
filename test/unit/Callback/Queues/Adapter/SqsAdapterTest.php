<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Callback\Queues\Adapter;

use Aws\Sqs\SqsClient;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;
use PHPUnit\Framework\TestCase;
use rollun\callback\Queues\Adapter\SqsAdapter;

class SqsAdapterTest extends TestCase
{
    protected function createObject($clientMock, $attributes = []): SqsAdapter
    {
        return new SqsAdapter($clientMock, null, $attributes);
    }

    public function testCreateQueue()
    {
        $clientMock = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()->getMock();

        $clientMock->method('__call')
            ->with('createQueue', [[
                'QueueName' => $this->getQueueName('a'),
                'Attributes' => [],
            ]]);

        $object = $this->createObject($clientMock);
        $object->createQueue('a');
        $this->assertTrue(true);
    }

    public function testCreateQueueWithAttributes()
    {
        $clientMock = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()->getMock();

        $collectionMock = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $collectionMock->method('get');
        $clientMock->expects($this->at(0))->method('__call')->with('getQueueUrl')->willReturn($collectionMock);

        $modelMock = $this->getMockBuilder(Model::class)->disableOriginalConstructor()->getMock();
        $modelMock->method('get')->willReturn([['priority' => 1, 'ReceiptHandle' => 1, 'Body' => serialize('dsd')]]);

        $clientMock->expects($this->at(1))->method('__call')->with('receiveMessage')
            ->willReturn($modelMock);

        $object = $this->createObject($clientMock, ['VisibilityTimeout' => 30]);
        $object->getMessages('a');
        $this->assertTrue(true);
    }

    public function testCreateQueueWithAttributesAndDelete()
    {
        $clientMock = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()->getMock();

        $collectionMock = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $collectionMock->method('get');
        $clientMock->expects($this->at(0))->method('__call')->with('getQueueUrl')->willReturn($collectionMock);

        $modelMock = $this->getMockBuilder(Model::class)->disableOriginalConstructor()->getMock();
        $modelMock->method('get')->willReturn([['priority' => 1, 'ReceiptHandle' => 1, 'Body' => serialize('dsd')]]);

        $clientMock->expects($this->at(1))->method('__call')->with('receiveMessage')
            ->willReturn($modelMock);

        $collectionMock = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $collectionMock->method('get');
        $clientMock->expects($this->at(2))->method('__call')->with('getQueueUrl')->willReturn($collectionMock);

        $object = $this->createObject($clientMock, ['VisibilityTimeout' => null]);
        $object->getMessages('a');
        $this->assertTrue(true);
    }

    public function testGetMessageAndDelete()
    {
        $clientMock = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()->getMock();

        $clientMock->method('__call')
            ->with('createQueue', [[
                'QueueName' => $this->getQueueName('a', [1, 2, 3]),
                'Attributes' => [1, 2, 3],
            ]]);

        $object = $this->createObject($clientMock, [1, 2, 3]);
        $object->createQueue('a');
        $this->assertTrue(true);
    }

    protected function getQueueName($queueName, $attributes = [], $prioritySuffix = '')
    {
        ksort($attributes);
        $attributeHash = md5(json_encode($attributes));

        return $attributeHash. $queueName . $prioritySuffix;
    }
}

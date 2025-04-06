<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Functional\Callback\Queues\Adapter;

use Psr\Container\ContainerInterface;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use rollun\callback\Queues\Adapter\SqsAdapter;

class SqsAdapterTest extends AbstractAdapterTest
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function createObject($timeInFlight = 0): AdapterInterface
    {
        if (getenv('AWS_KEY') === false) {
            $this->markTestSkipped('No aws key set');
        }
        return new SqsAdapter([
            'key' => getenv('AWS_KEY'),
            'secret'  => getenv('AWS_SECRET'),
            'region' => getenv('AWS_REGION'),
        ], null, [
            'VisibilityTimeout' => $timeInFlight,
        ]);
    }

    protected function getContainer()
    {
        if ($this->container == null) {
            $this->container = require 'config/container.php';
        }

        return $this->container;
    }

    /*public function testCreateAdapterWithDeadLetterQueue()
    {
        $adapter = $this->getContainer()->get('testDeadLetterSqsAdapter');

        $deadLetterQueue = $this->getContainer()->get('deadLetter');

        $adapter->createQueue('testQueue');
        $adapter->addMessage('testQueue', 'a');
        $adapter->getMessages('testQueue');

        sleep(2);
        $messages = $deadLetterQueue->getMessage();
        $adapter->deleteQueue('testQueue');
        $this->assertEquals($messages->getMessage()['Body'], 'a');
    }*/
}

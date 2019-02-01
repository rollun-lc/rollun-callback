<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\Callback\Queues\Adapter;

use Aws\Sqs\SqsClient;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use rollun\callback\Queues\Adapter\SqsAdapter;

class SqsAdapterTest extends AbstractAdapterTest
{
    protected function createObject($timeInFlight = 0): AdapterInterface
    {
        $sqsClient = SqsClient::factory([
            'key' => getenv('AWS_KEY'),
            'secret'  => getenv('AWS_SECRET'),
            'region' => getenv('AWS_REGION'),
        ]);

        return new SqsAdapter($sqsClient, null, [
            'VisibilityTimeout' => $timeInFlight,
        ]);
    }
}

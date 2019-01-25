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
            'key' => 'AKIAIDZAP6SF5TK6AN2Q',
            'secret'  => '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',
            'region' => 'eu-north-1',
        ]);

        return new SqsAdapter($sqsClient, null, [
            'VisibilityTimeout' => $timeInFlight,
        ]);
    }
}

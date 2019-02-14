<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Queues\Factory;

use rollun\callback\Queues\DeadLetterQueue;

class DeadLetterQueueFactory
{
    public function __invoke()
    {
        return new DeadLetterQueue([
            'key' => getenv('AWS_KEY'),
            'secret'  => getenv('AWS_SECRET'),
            'region' => getenv('AWS_REGION'),
        ]);
    }
}

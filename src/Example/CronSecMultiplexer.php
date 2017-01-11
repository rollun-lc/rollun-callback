<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.17
 * Time: 17:32
 */

namespace rollun\callback\Example;

use rollun\callback\Callback\Interruptor\Multiplexer;
use rollun\callback\Callback\Interruptor\Process;
use rollun\callback\Callback\Interruptor\Queue as QueueInterruptor;
use rollun\callback\Queues\Extractor;
use rollun\callback\Queues\Queue;

class CronSecMultiplexer extends Multiplexer
{
    const QUERY_NAME = 'test_cron_sec_multiplexer';

    public function __construct($interruptors)
    {
        parent::__construct($interruptors);
        $queue = new Queue(static::QUERY_NAME);
        $this->interruptors[] = new Process(function () use ($queue) {
            $extractor = new Extractor($queue);
            $extractor->extract();
        });
    }

}

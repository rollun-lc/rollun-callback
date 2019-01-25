<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\Callback\Queues\Adapter;

use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use rollun\callback\Queues\Adapter\FileAdapter;

class FileAdapterTest extends AbstractAdapterTest
{
    protected function createObject($timeInFlight = null): AdapterInterface
    {
        $repository = rtrim(getenv('FILE_ADAPTER_REPOSITORY'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return new FileAdapter($repository, $timeInFlight);
    }
}

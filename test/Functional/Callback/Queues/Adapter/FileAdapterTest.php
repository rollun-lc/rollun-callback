<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Functional\Callback\Queues\Adapter;

use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use rollun\callback\Queues\Adapter\FileAdapter;

class FileAdapterTest extends AbstractAdapterTest
{
    protected function createObject($timeInFlight = null): AdapterInterface
    {
        $dir = getenv('FILE_ADAPTER_REPOSITORY') === false ? sys_get_temp_dir() : getenv('FILE_ADAPTER_REPOSITORY');

        $repository = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return new FileAdapter($repository, $timeInFlight);
    }
}

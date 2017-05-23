<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 21.03.17
 * Time: 15:00
 */

namespace rollun\callback\Callback\Example;

use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\installer\Command;

class SecCallback implements InterruptorInterface
{
    const FILE = 'interrupt_sec';

    const PREFIX_TEXT = 'SEC_FILE_NAME';
    /**
     * @param $value
     * @return array
     * array contains field
     *
     */
    public function __invoke($value)
    {
        $time = microtime(true);
        file_put_contents(
            Command::getDataDir() . DIRECTORY_SEPARATOR . static::FILE,
            static::PREFIX_TEXT . ": {$value} [" . microtime(true) . "]\n",
            FILE_APPEND
        );
        return [$time];
    }
}

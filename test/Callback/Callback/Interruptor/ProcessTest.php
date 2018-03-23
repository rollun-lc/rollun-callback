<?php

namespace rollun\test\callback\Interruptor\Callback;

use rollun\callback\Callback\Callback;
use rollun\installer\Command;
use rollun\callback\Callback\Interruptor\Process;
use rollun\test\callback\Callback\CallbackTestDataProvider;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-10-13 at 12:52:54.
 */
class ProcessTest extends CallbackTestDataProvider
{

    protected function setUp()
    {
        //$container = include 'config/container.php';
       //InsideConstruct::setContainer($container);
    }
    

    public function test__parallelProcess()
    {
        $callback = new Callback(function ($file) {
            sleep(1);
            $time = microtime(1);
            file_put_contents($file, "$time\n", FILE_APPEND);
        });

        $outPutFile = Command::getDataDir() . "testOutput.dat";

        (new Process($callback))($outPutFile);
        (new Process($callback))($outPutFile);
        sleep(3);
        $timeData = file_get_contents($outPutFile);
        list($firstTime, $secondTime) = explode("\n", $timeData);
        if (abs($firstTime - $secondTime) < 0.5) {
            $result = 'parallel';
        } else {
            $result = 'in series';
        }
        if (substr(php_uname(), 0, 7) === "Windows") {
            $this->assertEquals('in series', $result);
        } else {
            $this->assertEquals('parallel', $result);
        }
    }

}

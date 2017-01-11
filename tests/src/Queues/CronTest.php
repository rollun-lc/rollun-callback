<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 05.01.17
 * Time: 10:30
 */

namespace rollun\test\callback\Queues;

use Interop\Container\ContainerInterface;
use rollun\callback\Queues\Extractor;
use rollun\callback\Queues\Queue;
use rollun\callback\Callback\Interruptor\Queue as QueueInterruptor;
use rollun\dic\InsideConstruct;
use rollun\callback\Ticker\Example\TickerCron;
use rollun\installer\Command;
use Zend\Http\Client;

class CronTest extends \PHPUnit_Framework_TestCase
{
    /** @var Queue */
    protected $minQueue;

    /** @var Queue */
    protected $secQueue;

    protected $url;

    protected $config;

    public function setUp()
    {
        $this->secQueue = new Queue('test_cron_sec_multiplexer');
        $this->minQueue = new Queue('test_cron_min_multiplexer');

        /** @var ContainerInterface $container */
        $container = include 'config/container.php';
        $this->config = $container->get('config');
        $this->url = $this->config['cronQueue']['url'];
        InsideConstruct::setContainer($container);

        $this->deleteJob();
        fopen(Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_min', 'w');
        fopen(Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_sec', 'w');
    }

    protected function deleteJob()
    {
        if (file_exists(Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_sec')) {
            unlink(Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_sec');
        }
        if (file_exists(Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_min')) {
            unlink(Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_min');
        }
    }

    public function test__cron()
    {
        $this->setJob();
        $httpClient = new Client($this->url);
        $headers['Content-Type'] = 'text/text';
        $headers['Accept'] = 'application/json';
        $httpClient->setHeaders($headers);
        $httpClient->setMethod('POST');
        $req = $httpClient->send();

        $this->assertTrue($req->isOk());

        sleep(3);

        $minFileData = file_get_contents(Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_min');
        $secFileData = file_get_contents(Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_sec');
        $data = explode(';', $minFileData);
        $this->assertEquals(1, count(array_diff($data, [''])));
        $this->assertEquals(2, count(array_diff(explode(';', $secFileData), [''])));

        $this->deleteJob();
    }

    protected function setJob()
    {
        $interruptorSecQueue = new QueueInterruptor(function ($value) {
            file_put_contents(
                Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_sec',
                "SEC_FILE_NAME: $value" . ";",
                FILE_APPEND
            );
        }, $this->secQueue);

        $interruptorMinQueue = new QueueInterruptor(function ($value) {
            file_put_contents(
                Command::getPublicDir() . DIRECTORY_SEPARATOR . 'interrupt_min',
                "MIN_FILE_NAME: $value" . ";",
                FILE_APPEND
            );
        }, $this->minQueue);

        $this->callJob($interruptorMinQueue, 3);
        $this->callJob($interruptorSecQueue, 2);
    }

    protected function callJob(callable $callback, $count)
    {
        for ($i = 0; $i < $count; $i++) {
            call_user_func($callback, $i + 1 . ":$count");
        }
    }
}

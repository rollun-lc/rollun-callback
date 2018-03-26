<?php

namespace rollun\test\callback\Queues;

use Interop\Container\ContainerInterface;
use rollun\callback\Queues\Factory\Installer\MainQueueInstaller;
use rollun\callback\Queues\Queue;
use rollun\callback\Callback\Interruptor\Queue as QueueInterruptor;
use rollun\installer\Command;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;
use Zend\Http\Client;

class MainQueueTest extends \PHPUnit_Framework_TestCase
{

    const FILE = 'mainQueueTest';
    /**
     * @var QueueInterface
     */
    protected $object;

    protected $url;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->url = 'http://' . constant("HOST") . "/api/webhook/cron";
        parent::__construct($name, $data, $dataName);
    }

    protected function setUp()
    {
        /** @var ContainerInterface $container */
        $container = include 'config/container.php';
        InsideConstruct::setContainer($container);
        $this->object = $container->get(MainQueueInstaller::MAIN_SEC_QUEUE);
    }

    public function testInterruptCallback()
    {
        $file = Command::getDataDir() . static::FILE;
        if(file_exists($file)) {
            unlink($file);
        }
        $callback = function ($value) use ($file) {
            $time = microtime(true);
            file_put_contents(
                $file,
                 "{$value};" . microtime(true) . "\n",
                FILE_APPEND
            );
            return [$time];
        };
        $queueInterrupt = new QueueInterruptor($callback, $this->object);
        $countTask =  10;
        for ($i = 0; $i < $countTask; $i++) {
            $queueInterrupt($i);
        }

        $httpClient = new Client($this->url, ["timeout" => 50]);
        $headers['Content-Type'] = 'text/text';
        $headers['Accept'] = 'application/json';
        $httpClient->setHeaders($headers);
        $httpClient->setMethod('POST');
        $req = $httpClient->send();

        $this->assertTrue($req->isOk());
        sleep(11);

        $mainFileData = file_get_contents($file);
        $data = array_diff(explode("\n", $mainFileData), ['']);
        $this->assertEquals($countTask, count($data));
    }

}

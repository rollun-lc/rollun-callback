<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Callback\Interruptor;

use PHPUnit\Framework\TestCase;
use Laminas\Http\Client;
use Laminas\ServiceManager\ServiceManager;

class CronTest extends TestCase
{
    protected $url;

    /**
     * @var ServiceManager
     */
    protected $container;

    protected function getContainer(): ServiceManager
    {
        if ($this->container === null) {
            $this->container = require 'config/container.php';
        }

        return $this->container;
    }

    protected function setUp(): void
    {
        $this->url = getenv("HOST") . '/api/webhook/cron';
        $this->deleteJob();
    }

    protected function tearDown(): void
    {
        $this->deleteJob();
    }

    protected function deleteJob()
    {
        if (file_exists('data' . DIRECTORY_SEPARATOR . 'interrupt_min')) {
            unlink('data' . DIRECTORY_SEPARATOR . 'interrupt_min');
        }
    }

    public function testCron()
    {
        $httpClient = new Client($this->url, ["timeout" => 65]);
        $headers['Content-Type'] = 'text/text';
        $headers['Accept'] = 'application/json';
        $httpClient->setHeaders($headers);
        $httpClient->setMethod('POST');
        $req = $httpClient->send();

        $this->assertTrue($req->getStatusCode() >= 200 && $req->getStatusCode() < 300);

        sleep(5);

        $minFileData = file_get_contents('data' . DIRECTORY_SEPARATOR . 'interrupt_min');
        $data = explode("\n", $minFileData);
        $this->assertEquals(4, count(array_diff($data, [''])));
    }

    public function testCronError()
    {
        $httpClient = new Client($this->url, ["timeout" => 65]);
        $headers['Content-Type'] = 'text/text';
        $headers['Accept'] = 'application/json';
        $httpClient->setHeaders($headers);
        $httpClient->setMethod('POST');
        $req = $httpClient->send();

        $this->assertTrue($req->getStatusCode() >= 200 && $req->getStatusCode() < 300);

        sleep(5);

        $minFileData = file_get_contents('data' . DIRECTORY_SEPARATOR . 'interrupt_min');
        $data = explode("\n", $minFileData);
        $this->assertEquals(4, count(array_diff($data, [''])));
    }
}

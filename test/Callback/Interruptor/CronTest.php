<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\Queues;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use rollun\dic\InsideConstruct;
use Zend\Http\Client;

class CronTest extends TestCase
{
    protected $url;

    protected $config;

    public function setUp()
    {
        /** @var ContainerInterface $container */
        $container = include 'config/container.php';
        $this->config = $container->get('config');

        $this->url = getenv("HOST") . '/api/webhook/cron';

        InsideConstruct::setContainer($container);
        $this->deleteJob();
    }

    protected function tearDown()
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

        $this->assertTrue($req->getStatusCode() > 200 && $req->getStatusCode() < 300);

        sleep(60);

        $minFileData = file_get_contents('data' . DIRECTORY_SEPARATOR . 'interrupt_min');
        $data = explode("\n", $minFileData);
        $this->assertEquals(4, count(array_diff($data, [''])));
    }
}

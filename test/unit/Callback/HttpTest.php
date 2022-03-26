<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Callback;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Http;
use Laminas\ServiceManager\ServiceManager;

class HttpTest extends TestCase
{
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

    public function testInvokeRemoteInterrupter()
    {
        $url = getenv("HOST") . '/api/webhook/testInterrupter';
        $object = new Http($url);
        $payload = $object();

        $this->assertTrue(isset($payload->getPayload()['stdout']));
        $this->assertTrue(isset($payload->getPayload()['stderr']));
        $this->assertTrue(isset($payload->getPayload()['interrupter_type']));
    }

    public function testInvokeRemoteCallback()
    {
        $url = getenv("HOST") . '/api/webhook/testCallback';
        $object = new Http($url);
        $payload = $object('Word');

        $this->assertEquals('Hello Word', $payload);
    }
}

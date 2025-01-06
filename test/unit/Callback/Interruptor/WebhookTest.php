<?php


namespace rollun\test\unit\Callback\Interruptor;


use PHPUnit\Framework\TestCase;
use Laminas\Http\Client;
use Laminas\ServiceManager\ServiceManager;

class WebhookTest extends TestCase
{
    protected $url;

    protected $client;

    /**
     * @var ServiceManager
     */
    protected $container;

    protected function setUp(): void
    {
        if (getenv("HOST") === false) {
            $this->markTestSkipped('No HOST environment variable');
        }
        $this->url = getenv("HOST") . '/api/webhook/webhookCallback';
        $this->client = new Client($this->url, ["timeout" => 65]);
        $headers['Content-Type'] = 'text/text';
        $headers['Accept'] = 'application/json';
        $this->client->setHeaders($headers);
        $this->client->setMethod('POST');
    }

    public function testWebhookPrimitive()
    {
        $this->client->setRawBody('primitive');
        $response = $this->client->send();

        $this->assertTrue($response->getStatusCode() >= 200 && $response->getStatusCode() < 300);
        $this->assertIsString($response->getBody());
    }

    public function testWebhookArray()
    {
        $this->client->setRawBody('array');
        $response = $this->client->send();

        $result = json_decode($response->getBody());

        $this->assertTrue($response->getStatusCode() >= 200 && $response->getStatusCode() < 300);
        $this->assertEquals('success', $result->result);
    }

    public function testWebhookError()
    {
        $this->client->setRawBody('error');
        $response = $this->client->send();

        $this->assertTrue($response->getStatusCode() === 500);
        $result = json_decode($response->getBody());
        $this->assertNotEmpty($result->error);
    }

    public function testWebhookException()
    {
        $this->client->setRawBody('exception');
        $response = $this->client->send();

        $this->assertTrue($response->getStatusCode() === 500);
        $result = json_decode($response->getBody());
        $this->assertNotEmpty($result->error);
    }
}
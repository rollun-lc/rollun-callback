<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback\Interruptor;

use PHPUnit\Framework\TestCase;
use Laminas\Http\Client;
use Laminas\ServiceManager\ServiceManager;

class CronTest extends TestCase
{
    /**
     * cronMultiplexer fans the webhook out to that many cronCallback services.
     */
    private const EXPECTED_CALLBACK_COUNT = 4;

    /**
     * The webhook only spawns a detached child and answers with its PID, so the result has to be
     * waited for rather than slept on: the child boots the whole application before the first
     * callback writes anything, which takes noticeably longer on a cold CI runner.
     */
    private const CHILD_TIMEOUT_SEC = 30;

    private const JOB_FILE = 'data' . DIRECTORY_SEPARATOR . 'interrupt_min';

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
        if (getenv("HOST") === false) {
            $this->markTestSkipped('No HOST environment variable');
        }
        $this->url = getenv("HOST") . '/api/webhook/cron';
        $this->deleteJob();
    }

    protected function tearDown(): void
    {
        $this->deleteJob();
    }

    protected function deleteJob()
    {
        if (file_exists(self::JOB_FILE)) {
            unlink(self::JOB_FILE);
        }
    }

    public function testCron()
    {
        $this->postToCronWebhook();

        $this->assertEveryCallbackRan();
    }

    public function testCronError()
    {
        $this->postToCronWebhook();

        $this->assertEveryCallbackRan();
    }

    private function postToCronWebhook(): void
    {
        $httpClient = new Client($this->url, ["timeout" => 65]);
        $headers['Content-Type'] = 'text/text';
        $headers['Accept'] = 'application/json';
        $httpClient->setHeaders($headers);
        $httpClient->setMethod('POST');
        $req = $httpClient->send();

        $this->assertTrue($req->getStatusCode() >= 200 && $req->getStatusCode() < 300);
    }

    private function assertEveryCallbackRan(): void
    {
        $lines = $this->waitForCallbackLines();

        $this->assertCount(
            self::EXPECTED_CALLBACK_COUNT,
            $lines,
            sprintf(
                'The webhook answered with success, but the child process wrote %d of %d callback lines '
                . 'to %s within %d s.',
                count($lines),
                self::EXPECTED_CALLBACK_COUNT,
                self::JOB_FILE,
                self::CHILD_TIMEOUT_SEC
            )
        );
    }

    /**
     * @return string[]
     */
    private function waitForCallbackLines(): array
    {
        $deadline = microtime(true) + self::CHILD_TIMEOUT_SEC;
        $lines = [];

        do {
            clearstatcache(true, self::JOB_FILE);

            if (is_file(self::JOB_FILE)) {
                $content = (string) file_get_contents(self::JOB_FILE);
                $lines = array_values(array_diff(explode("\n", $content), ['']));

                if (count($lines) >= self::EXPECTED_CALLBACK_COUNT) {
                    break;
                }
            }

            usleep(200_000);
        } while (microtime(true) < $deadline);

        return $lines;
    }
}

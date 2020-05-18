<?php
declare(strict_types=1);

namespace rollun\callback\Callback\HealthChecker;

use rollun\callback\Callback\Http;

/**
 * Class PingHealthChecker
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class PingHealthChecker extends AbstractHealthChecker
{
    const KEY_HOST = 'host';

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        $host = $this->getHost();

        $object = new Http($host . '/api/webhook/ping');
        $payload = $object();

        if (isset($payload['error'])) {
            $this->addMessage("Service '$host' unavailable. Response: " . json_encode($payload));

            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getHost(): string
    {
        return $this->config[self::KEY_HOST];
    }
}

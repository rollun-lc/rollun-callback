<?php
declare(strict_types=1);

namespace rollun\callback\Callback\HealthChecker;

use Zend\Http\Client;

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
        $service = $this->getHost();

        $client = new Client($service . '/api/webhook/ping');

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        $headers['APP_ENV'] = getenv('APP_ENV');

        $client->setHeaders($headers);

        $client->setMethod('GET');

        $response = $client->send();

        if (!$response->isSuccess()) {
            $this->addMessage("Service '$service' unavailable");

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

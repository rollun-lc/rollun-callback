<?php
declare(strict_types=1);

namespace rollun\callback\Callback\HealthChecker\Validator;

use rollun\callback\Callback\Http;

/**
 * Class PingValidator
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class PingValidator extends AbstractValidator
{
    const KEY_HOST = 'host';

    /**
     * @var array
     */
    protected $config;

    /**
     * PingValidator constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        $host = $this->config[self::KEY_HOST];

        $object = new Http($host . '/api/webhook/ping');
        $payload = $object();

        if (isset($payload['error'])) {
            $this->addMessage("Service '$host' unavailable. Response: " . json_encode($payload));

            return false;
        }

        return true;
    }
}

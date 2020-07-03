<?php
declare(strict_types=1);

namespace rollun\callback\Callback\HealthChecker\Validator;

use rollun\callback\Callback\Http;
use Zend\Http\Exception\RuntimeException;

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
    const KEY_OPTIONS = 'options';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * PingValidator constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        if (!empty($this->config[self::KEY_OPTIONS])) {
            $this->options = $this->config[self::KEY_OPTIONS];
        }
    }

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        if (empty($this->config[self::KEY_HOST])) {
            $this->addMessage("Host is not set for PingValidator");

            return false;
        }

        $host = $this->config[self::KEY_HOST];

        $object = new Http($host . '/api/webhook/ping', $this->options);

        try {
            $payload = $object();
        } catch (RuntimeException $exception) {
            $this->addMessage("Service '$host' unavailable. Response: " . $exception->getMessage());

            return false;
        }

        if (isset($payload['error'])) {
            $this->addMessage("Service '$host' unavailable. Response: " . json_encode($payload));

            return false;
        }

        return true;
    }
}

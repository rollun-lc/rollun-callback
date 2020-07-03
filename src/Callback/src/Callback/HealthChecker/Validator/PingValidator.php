<?php
declare(strict_types=1);

namespace rollun\callback\Callback\HealthChecker\Validator;

use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Http;
use rollun\dic\InsideConstruct;
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * PingValidator constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        InsideConstruct::init(
            [
                'logger' => LoggerInterface::class
            ]
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(
            [
                'logger' => LoggerInterface::class
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        // run validation
        $isValid = $this->runValidation($value);

        // send metrics to prometheus
        $this->sendPrometheusMetrics($isValid);

        return $isValid;
    }

    /**
     * @param bool $isValid
     */
    protected function sendPrometheusMetrics(bool $isValid): void
    {
        if ($this->logger instanceof LoggerInterface) {
            $metricData = [
                'metricId' => 'ping',
                'value'    => ($isValid) ? 1 : 0,
                'groups'   => ['host' => $this->getHost()],
            ];

            $this->logger->notice('METRICS_GAUGE', $metricData);
        }
    }

    /**
     * @param $value
     *
     * @return bool
     */
    protected function runValidation($value): bool
    {
        if (empty($this->config[self::KEY_HOST])) {
            $this->addMessage("Host is not set for PingValidator");

            return false;
        }

        // prepare options
        $options = [];
        if (!empty($this->config[self::KEY_OPTIONS])) {
            $options = $this->config[self::KEY_OPTIONS];
        }

        $host = $this->getHost();

        $object = new Http($host . '/api/webhook/ping', $options);

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

    /**
     * @return string
     */
    protected function getHost(): string
    {
        return $this->config[self::KEY_HOST];
    }
}

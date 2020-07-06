<?php
declare(strict_types=1);

namespace rollun\callback\Callback\HealthChecker;

use Cron\CronExpression;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\HealthChecker\Validator\AbstractValidator;
use rollun\dic\InsideConstruct;
use Zend\Validator\ValidatorInterface;

/**
 * Class HealthChecker
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class HealthChecker
{
    /**
     * @var CronExpression
     */
    protected $cronExpression;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var string
     */
    protected $level;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * HealthChecker constructor.
     *
     * @param CronExpression       $cronExpression
     * @param ValidatorInterface   $validator
     * @param string               $level
     * @param LoggerInterface|null $logger
     *
     * @throws \ReflectionException
     */
    public function __construct(CronExpression $cronExpression, ValidatorInterface $validator, string $level, LoggerInterface $logger = null)
    {
        $this->cronExpression = $cronExpression;
        $this->validator = $validator;
        $this->level = $level;

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
     * @return array
     */
    public function __sleep()
    {
        return ['cronExpression', 'validator', 'level'];
    }

    /**
     * @param mixed $value
     */
    public function __invoke($value = null)
    {
        if ($this->cronExpression->isDue()) {
            $isValid = $this->validator->isValid($value);
            if (!$isValid) {
                foreach ($this->validator->getMessages() as $message) {
                    $this->logger->log($this->level, $message);
                }
            }

            // send metrics to prometheus
            $this->sendMetricsToPrometheus($isValid);
        }
    }

    /**
     * @param bool $isValid
     */
    protected function sendMetricsToPrometheus(bool $isValid): void
    {
        if ($this->validator instanceof AbstractValidator && !empty($metricData = $this->validator->getMetricDataForPrometheus($isValid))) {
            $this->logger->notice('METRICS_GAUGE', $metricData);
        }
    }
}

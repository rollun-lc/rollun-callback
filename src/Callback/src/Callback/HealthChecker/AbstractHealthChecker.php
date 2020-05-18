<?php
declare(strict_types=1);

namespace rollun\callback\Callback\HealthChecker;

use Cron\CronExpression;
use Jaeger\Tracer\Tracer;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Factory\HealthCheckerAbstractFactory;
use rollun\dic\InsideConstruct;
use Zend\Validator\ValidatorInterface;

/**
 * Abstract class AbstractHealthChecker
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
abstract class AbstractHealthChecker implements ValidatorInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Tracer
     */
    protected $tracer;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var CronExpression
     */
    protected $expression;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * AbstractHealthChecker constructor.
     *
     * @param array                $config
     * @param LoggerInterface|null $logger
     * @param Tracer|null          $tracer
     *
     * @throws \ReflectionException
     */
    public function __construct(array $config, LoggerInterface $logger = null, Tracer $tracer = null)
    {
        $this->config = $config;
        $this->expression = CronExpression::factory($this->config['expression']);

        InsideConstruct::init(
            [
                'logger' => LoggerInterface::class,
                'tracer' => Tracer::class,
            ]
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        $this->expression = CronExpression::factory($this->config['expression']);

        InsideConstruct::initWakeup(
            [
                'logger' => LoggerInterface::class,
                'tracer' => Tracer::class,
            ]
        );
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['config'];
    }

    /**
     * @param mixed $value
     */
    public function __invoke($value = null)
    {
        if ($this->expression->isDue()) {
            if (!$this->isValid($value)) {
                foreach ($this->getMessages() as $message) {
                    $this->logger->log($this->config[HealthCheckerAbstractFactory::KEY_LEVEL], $message);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    protected function addMessage(string $message): AbstractHealthChecker
    {
        $this->messages[] = $message;

        return $this;
    }
}

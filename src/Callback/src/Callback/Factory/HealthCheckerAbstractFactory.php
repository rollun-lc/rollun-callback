<?php
declare(strict_types=1);

namespace rollun\callback\Callback\Factory;

use Cron\CronExpression;
use Interop\Container\ContainerInterface;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\HealthChecker\HealthChecker;

/**
 * Class HealthCheckerAbstractFactory
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class HealthCheckerAbstractFactory extends CallbackAbstractFactoryAbstract
{
    const KEY_CRON_EXPRESSION = 'cronExpression';
    const KEY_LOG_LEVEL = 'logLevel';
    const KEY_VALIDATOR = 'validator';

    const DEFAULT_CLASS = HealthChecker::class;

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $options ?? $container->get('config')[static::KEY][$requestedName];

        if (!isset($config[static::KEY_CRON_EXPRESSION])) {
            throw new CallbackException(static::KEY_CRON_EXPRESSION . " not been set.");
        }

        if (!isset($config[static::KEY_LOG_LEVEL])) {
            $config[static::KEY_LOG_LEVEL] = 'warning';
        }

        if (!isset($config[static::KEY_VALIDATOR])) {
            throw new CallbackException(static::KEY_VALIDATOR . " not been set.");
        }

        // get cron expression
        $cronExpression = CronExpression::factory($config[static::KEY_CRON_EXPRESSION]);

        // get validator
        $validator = $container->get($config[static::KEY_VALIDATOR][static::KEY_CLASS]);

        return new $config[static::KEY_CLASS]($cronExpression, $validator, $config[static::KEY_LOG_LEVEL]);
    }
}

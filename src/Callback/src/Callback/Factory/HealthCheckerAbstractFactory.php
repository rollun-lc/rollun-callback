<?php
declare(strict_types=1);

namespace rollun\callback\Callback\Factory;

use Interop\Container\ContainerInterface;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\HealthChecker\AbstractHealthChecker;

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
    const KEY_EXPRESSION = 'expression';
    const KEY_LEVEL = 'level';

    const DEFAULT_CLASS = AbstractHealthChecker::class;

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $options ?? $container->get('config')[static::KEY][$requestedName];

        $class = $config[static::KEY_CLASS];

        if (!isset($config[static::KEY_EXPRESSION])) {
            throw new CallbackException(static::KEY_EXPRESSION . " not been set.");
        }

        if (!isset($config[static::KEY_LEVEL])) {
            $config[static::KEY_LEVEL] = 'warning';
        }

        return new $class($config);
    }
}

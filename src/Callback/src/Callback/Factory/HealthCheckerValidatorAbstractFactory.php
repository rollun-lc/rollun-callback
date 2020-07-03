<?php
declare(strict_types=1);

namespace rollun\callback\Callback\Factory;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\HealthChecker\Validator\AbstractValidator;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Class HealthCheckerValidatorAbstractFactory
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class HealthCheckerValidatorAbstractFactory implements AbstractFactoryInterface
{
    const KEY = CallbackAbstractFactoryAbstract::KEY;
    const KEY_CLASS = HealthCheckerAbstractFactory::KEY_CLASS;
    const KEY_VALIDATOR = HealthCheckerAbstractFactory::KEY_VALIDATOR;

    /**
     * @var string
     */
    protected $callbackName;

    /**
     * @inheritDoc
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (empty($container->get('config')[self::KEY])) {
            return false;
        }

        $callbacks = $container->get('config')[self::KEY];

        if (!is_array($callbacks)) {
            return false;
        }

        foreach ($callbacks as $callbackName => $callback) {
            if (!empty($callback[self::KEY_VALIDATOR][self::KEY_CLASS]) && $callback[self::KEY_VALIDATOR][self::KEY_CLASS] == $requestedName) {
                $this->callbackName = $callbackName;
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // get config
        $config = $container->get('config')[self::KEY][$this->callbackName][self::KEY_VALIDATOR];

        $validatorName = $config[self::KEY_CLASS];
        $validator = ($container->has($validatorName)) ? $container->get($validatorName) : new $validatorName();
        if (!$validator instanceof AbstractValidator) {
            throw new \Exception('Validator should be instance of ' . AbstractValidator::class);
        }
        $validator->setConfig($config);

        return $validator;
    }
}

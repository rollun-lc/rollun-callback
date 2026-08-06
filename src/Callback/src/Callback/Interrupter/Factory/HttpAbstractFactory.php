<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter\Factory;

use Psr\Container\ContainerInterface;
use rollun\callback\Callback\Factory\HttpClientAbstractFactory;

/**
 * Builds a plain {@see \rollun\callback\Callback\Http} client for services declared under
 * the `interrupt` config key.
 *
 * @deprecated Declare such services under the `callback` config key and let
 *             {@see HttpClientAbstractFactory} build them instead. `Http` is not an interrupter:
 *             it neither implements `InterrupterInterface` nor returns a `PayloadInterface`,
 *             so it does not belong under the `interrupt` key. This class is kept only so that
 *             existing configs referencing it keep loading, and it is now a thin alias of
 *             {@see HttpClientAbstractFactory} with a different config key.
 *
 * FIXME: remove this class in the next major/minor release. Checklist:
 *        - drop both registrations in `ConfigProvider` (`dependencies` and the `interrupters`
 *          plugin manager);
 *        - drop the `httpInterrupter` example from `docs/index.md` (and the back-reference to
 *          that service name in the multiplexer example above it);
 *        - migrate the remaining consumer: in service-carriers,
 *          `config/autoload/htb_shipping_methods.global.php` imports this class, registers it in
 *          `abstract_factories` and uses its `KEY_*` constants — that config must be switched to
 *          `HttpClientAbstractFactory` and released BEFORE this class disappears, otherwise the
 *          service fails to boot in `ServiceManager::configure()`.
 */
class HttpAbstractFactory extends HttpClientAbstractFactory
{
    public const KEY = 'interrupt';

    /**
     * @deprecated Ignored. `Http` takes no callback — it posts the invocation value to `url`.
     */
    public const KEY_CALLBACK_SERVICE = 'callbackService';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        trigger_error(
            sprintf(
                "'%s' is deprecated and will be removed in the next major/minor release. Move service '%s'"
                . " from the '%s' config key to the '%s' key, where it is built by '%s'.",
                self::class,
                $requestedName,
                self::KEY,
                HttpClientAbstractFactory::KEY,
                HttpClientAbstractFactory::class
            ),
            E_USER_DEPRECATED
        );

        return parent::__invoke($container, $requestedName, $options);
    }
}

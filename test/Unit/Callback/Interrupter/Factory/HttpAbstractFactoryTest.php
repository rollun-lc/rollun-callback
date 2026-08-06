<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback\Interrupter\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Factory\HttpClientAbstractFactory;
use rollun\callback\Callback\Http;
use rollun\callback\Callback\Interrupter\Factory\HttpAbstractFactory;
use rollun\callback\Callback\Interrupter\Process;

class HttpAbstractFactoryTest extends TestCase
{
    private const SERVICE_NAME = 'httpInterrupter';

    private const URL = 'http://example.com/api/webhook/ShippingCost/';

    /**
     * The config shape documented in docs/index.md: class + url + options, no callbackService.
     */
    public function testInvokeBuildsHttpClientFromDocumentedConfig()
    {
        $container = self::createContainer(self::config([
            HttpAbstractFactory::KEY_CLASS => Http::class,
            HttpAbstractFactory::KEY_URL => self::URL,
            HttpAbstractFactory::KEY_OPTIONS => ['timeout' => 42],
        ]));

        $service = self::invokeFactory($container);

        $this->assertInstanceOf(Http::class, $service);
    }

    /**
     * Pins the constructor argument order: url must land in $url and options in $options,
     * which is exactly what the factory used to get wrong.
     */
    public function testInvokePassesUrlAndOptionsToTheRightConstructorArguments()
    {
        $container = self::createContainer(self::config([
            HttpAbstractFactory::KEY_CLASS => Http::class,
            HttpAbstractFactory::KEY_URL => self::URL,
            HttpAbstractFactory::KEY_OPTIONS => ['timeout' => 42, 'method' => 'PUT'],
        ]));

        $service = self::invokeFactory($container);

        $this->assertSame('http://example.com/api/webhook/ShippingCost', self::readProperty($service, 'url'));
        $this->assertSame(['timeout' => 42], self::readProperty($service, 'options'));
        $this->assertSame('PUT', self::readProperty($service, 'method'));
    }

    public function testInvokeDefaultsToAnEmptyOptionsList()
    {
        $container = self::createContainer(self::config([
            HttpAbstractFactory::KEY_CLASS => Http::class,
            HttpAbstractFactory::KEY_URL => self::URL,
        ]));

        $service = self::invokeFactory($container);

        $this->assertSame([], self::readProperty($service, 'options'));
    }

    /**
     * `callbackService` is a leftover of the removed serialized-callback interrupter:
     * Http has no callback argument at all, so the key must not be required nor used.
     */
    public function testInvokeIgnoresTheLegacyCallbackServiceKey()
    {
        $container = self::createContainer(self::config([
            HttpAbstractFactory::KEY_CLASS => Http::class,
            HttpAbstractFactory::KEY_CALLBACK_SERVICE => 'someCallback',
            HttpAbstractFactory::KEY_URL => self::URL,
            HttpAbstractFactory::KEY_OPTIONS => ['timeout' => 42],
        ]) + ['someCallback' => static fn($value = null) => $value]);

        $service = self::invokeFactory($container);

        $this->assertInstanceOf(Http::class, $service);
        $this->assertSame('http://example.com/api/webhook/ShippingCost', self::readProperty($service, 'url'));
    }

    public function testInvokeThrowsWhenUrlIsMissing()
    {
        $container = self::createContainer(self::config([
            HttpAbstractFactory::KEY_CLASS => Http::class,
        ]));

        $this->expectException(CallbackException::class);
        $this->expectExceptionMessage(HttpAbstractFactory::KEY_URL . ' not been set.');

        self::invokeFactory($container);
    }

    public function testInvokeIsDeprecated()
    {
        $container = self::createContainer(self::config([
            HttpAbstractFactory::KEY_CLASS => Http::class,
            HttpAbstractFactory::KEY_URL => self::URL,
        ]));

        $deprecations = [];
        set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecations): bool {
                $deprecations[] = $errstr;

                return true;
            },
            E_USER_DEPRECATED
        );

        try {
            // deliberately not going through self::invokeFactory(), which silences deprecations
            (new HttpAbstractFactory())($container, self::SERVICE_NAME);
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString(HttpClientAbstractFactory::class, $deprecations[0]);
        $this->assertStringContainsString(HttpClientAbstractFactory::KEY, $deprecations[0]);
    }

    public function testCanCreateSuccess()
    {
        $container = self::createContainer(self::config([
            HttpAbstractFactory::KEY_CLASS => Http::class,
            HttpAbstractFactory::KEY_URL => self::URL,
        ]));

        $this->assertTrue((new HttpAbstractFactory())->canCreate($container, self::SERVICE_NAME));
    }

    public function testCanCreateFalseForForeignClass()
    {
        $container = self::createContainer(self::config([
            HttpAbstractFactory::KEY_CLASS => Process::class,
            HttpAbstractFactory::KEY_URL => self::URL,
        ]));

        $this->assertFalse((new HttpAbstractFactory())->canCreate($container, self::SERVICE_NAME));
    }

    public function testCanCreateFalseForUnknownService()
    {
        $container = self::createContainer(self::config([
            HttpAbstractFactory::KEY_CLASS => Http::class,
            HttpAbstractFactory::KEY_URL => self::URL,
        ]));

        $this->assertFalse((new HttpAbstractFactory())->canCreate($container, 'unknownService'));
    }

    /**
     * The factory is deprecated, so invoking it is expected to raise E_USER_DEPRECATED;
     * silence it here so that every other assertion stays independent of the phpunit
     * deprecation-to-exception setting.
     */
    private static function invokeFactory(ContainerInterface $container): mixed
    {
        set_error_handler(static fn(): bool => true, E_USER_DEPRECATED);

        try {
            return (new HttpAbstractFactory())($container, self::SERVICE_NAME);
        } finally {
            restore_error_handler();
        }
    }

    private static function config(array $serviceConfig): array
    {
        return [
            'config' => [
                HttpAbstractFactory::KEY => [
                    self::SERVICE_NAME => $serviceConfig,
                ],
            ],
        ];
    }

    private static function createContainer(array $config): ContainerInterface
    {
        return new class ($config) implements ContainerInterface {
            public function __construct(private array $config) {}

            public function get(string $id): mixed
            {
                return $this->config[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->config);
            }
        };
    }

    private static function readProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}

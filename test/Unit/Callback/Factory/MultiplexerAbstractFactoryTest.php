<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use rollun\callback\Callback\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Callback\Multiplexer\CallbackObject;
use rollun\callback\Callback\SerializedCallback;
use stdClass;

class MultiplexerAbstractFactoryTest extends TestCase
{
    private const SERVICE_NAME = 'testMultiplexer';

    /**
     * A service name that also happens to be the name of a global PHP function.
     * is_callable() is true for such a string, so it must not be allowed to shadow
     * the container lookup.
     */
    private const COLLIDING_SERVICE_NAME = 'array_sum';

    private const CONTAINER_RESULT = 'resolved-from-container';

    /**
     * The bug: 'array_sum' was turned into a Closure over the global function, so the
     * multiplexer summed its input instead of calling the registered service.
     */
    public function testInvokeResolvesServiceNameThatCollidesWithGlobalFunction()
    {
        $multiplexer = self::buildMultiplexer(
            [self::COLLIDING_SERVICE_NAME],
            [self::COLLIDING_SERVICE_NAME => self::invokableService(self::CONTAINER_RESULT)]
        );

        $this->assertSame([self::CONTAINER_RESULT], $multiplexer([1, 2, 3]));
    }

    /**
     * Callbacks taken from the container are named after their service id; the Closure
     * the bug produced instead carried no name at all.
     */
    public function testInvokeKeepsServiceNameForCallbackCollidingWithGlobalFunction()
    {
        $multiplexer = self::buildMultiplexer(
            [self::COLLIDING_SERVICE_NAME],
            [self::COLLIDING_SERVICE_NAME => self::invokableService(self::CONTAINER_RESULT)]
        );

        $callbacks = self::readCallbackObjects($multiplexer);

        $this->assertCount(1, $callbacks);
        $this->assertSame(self::COLLIDING_SERVICE_NAME, $callbacks[0]->getName());
    }

    /**
     * Control: a service id that collides with nothing must keep working exactly as before.
     */
    public function testInvokeResolvesOrdinaryServiceName()
    {
        $multiplexer = self::buildMultiplexer(
            ['some.callback.service'],
            ['some.callback.service' => self::invokableService(self::CONTAINER_RESULT)]
        );

        $this->assertSame([self::CONTAINER_RESULT], $multiplexer(null));
        $this->assertSame('some.callback.service', self::readCallbackObjects($multiplexer)[0]->getName());
    }

    /**
     * Guards the is_string() check in front of the container lookup: PSR-11 has() only
     * accepts strings, so a closure in the config must never reach it.
     */
    public function testInvokeWrapsClosureIntoSerializedCallback()
    {
        $multiplexer = self::buildMultiplexer([static fn($value = null) => 'from-closure'], []);

        $this->assertSame(['from-closure'], $multiplexer(null));
        $this->assertInstanceOf(SerializedCallback::class, self::readInnerCallback(self::readCallbackObjects($multiplexer)[0]));
    }

    /**
     * The callable-string fallback stays available for names the container does not know,
     * mirroring SerializedCallbackAbstractFactory.
     */
    public function testInvokeFallsBackToCallableStringWhenNotAContainerService()
    {
        $multiplexer = self::buildMultiplexer(['strrev'], []);

        $this->assertSame(['cba'], $multiplexer('abc'));
    }

    /**
     * CallbackObject is invokable, so is_callable() used to swallow it and rewrap it into an
     * unnamed SerializedCallback, dropping the name it exists to carry.
     */
    public function testInvokePreservesNameOfExplicitCallbackObject()
    {
        $callbackObject = new CallbackObject(static fn($value = null) => 'from-callback-object', 'myNamedCallback');

        $multiplexer = self::buildMultiplexer([$callbackObject], []);

        $callbacks = self::readCallbackObjects($multiplexer);

        $this->assertSame('myNamedCallback', $callbacks[0]->getName());
        $this->assertSame(['from-callback-object'], $multiplexer(null));
    }

    public function testInvokeLogsAlertForUnknownServiceName()
    {
        $logger = self::createSpyLogger();

        self::buildMultiplexer(
            ['some.callback.service', 'no.such.service'],
            ['some.callback.service' => self::invokableService(self::CONTAINER_RESULT)],
            $logger
        );

        $this->assertCount(1, $logger->records);
        $this->assertStringContainsString('no.such.service', $logger->records[0]);
    }

    /**
     * A value that is neither a service id nor a callback is a config mistake: it has to be
     * reported, not turned into a TypeError while the container is being built.
     */
    public function testInvokeLogsAlertInsteadOfFailingOnUnsupportedValue()
    {
        $logger = self::createSpyLogger();

        $multiplexer = self::buildMultiplexer(
            ['some.callback.service', new stdClass()],
            ['some.callback.service' => self::invokableService(self::CONTAINER_RESULT)],
            $logger
        );

        $this->assertCount(1, $logger->records);
        $this->assertStringContainsString('stdClass', $logger->records[0]);
        $this->assertSame([self::CONTAINER_RESULT], $multiplexer(null));
    }

    public function testCanCreateOnlyForMultiplexerConfig()
    {
        $factory = new MultiplexerAbstractFactory();
        $container = self::createContainer([], [], self::createSpyLogger());

        $this->assertTrue($factory->canCreate($container, self::SERVICE_NAME));
        $this->assertFalse($factory->canCreate($container, 'unknownService'));
    }

    private static function buildMultiplexer(
        array $interrupters,
        array $services,
        ?LoggerInterface $logger = null
    ): Multiplexer {
        $container = self::createContainer($interrupters, $services, $logger ?? self::createSpyLogger());

        return (new MultiplexerAbstractFactory())($container, self::SERVICE_NAME);
    }

    private static function createContainer(
        array $interrupters,
        array $services,
        LoggerInterface $logger
    ): ContainerInterface {
        $config = $services + [
            LoggerInterface::class => $logger,
            'config' => [
                MultiplexerAbstractFactory::KEY => [
                    self::SERVICE_NAME => [
                        MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
                        MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => $interrupters,
                    ],
                ],
            ],
        ];

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

    /**
     * A container service is an object, not a raw callable string - that is the whole
     * point of resolving it through the container.
     */
    private static function invokableService(string $result): object
    {
        return new class ($result) {
            public function __construct(private string $result) {}

            public function __invoke($value = null): string
            {
                return $this->result;
            }
        };
    }

    /**
     * @return LoggerInterface
     */
    private static function createSpyLogger()
    {
        return new class extends AbstractLogger {
            public array $records = [];

            public function log($level, $message, array $context = [])
            {
                $this->records[] = $level . ': ' . $message;
            }
        };
    }

    /**
     * @return CallbackObject[]
     */
    private static function readCallbackObjects(Multiplexer $multiplexer): array
    {
        return array_values(self::readProperty($multiplexer, 'callbacks') ?? []);
    }

    private static function readInnerCallback(CallbackObject $callbackObject): mixed
    {
        return self::readProperty($callbackObject, 'callback');
    }

    private static function readProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}

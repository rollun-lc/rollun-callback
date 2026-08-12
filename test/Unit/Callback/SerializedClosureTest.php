<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback;

use PHPUnit\Framework\TestCase;
use Rollun\Test\Support\RequiresWorkingOpisClosure;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\SerializedCallback;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use stdClass;
use WeakMap;

/**
 * Замыкание, пересекающее границу интерраптера, проходит через opis/closure.
 * На PHP с багом GH-8995 распаковка любого объекта в opis/closure v4 бросает
 * TypeError, поэтому такая задача не выполняется — при том, что родитель
 * рапортует об успехе.
 *
 * Баг присутствует в PHP 8.0.0-8.0.21 и 8.1.0-8.1.8, исправлен в 8.0.22 и 8.1.9.
 *
 * Тесты идут от первопричины к последствию: PHP -> opis -> SerializedCallback -> Process.
 *
 * Каждый из них по своей природе завязан на замыкание, поэтому обойти баг, сохранив
 * смысл, нельзя - на битой версии PHP они скипаются целиком.
 */
class SerializedClosureTest extends TestCase
{
    use RequiresWorkingOpisClosure;

    private const OUTPUT_FILE = 'data/serialized_closure_test.log';

    private const CHILD_TIMEOUT_SEC = 10;

    protected function setUp(): void
    {
        $this->skipIfPhpBreaksOpisClosure();
    }

    protected function tearDown(): void
    {
        if (is_file(self::OUTPUT_FILE)) {
            unlink(self::OUTPUT_FILE);
        }
    }

    /**
     * Первопричина: dim-обработчики WeakMap не разыменовывают zval-референс.
     * Стрелочную функцию здесь использовать нельзя — она захватывает по значению
     * и разыменовывает референс, из-за чего баг не воспроизводится.
     */
    public function testWeakMapAcceptsReferencedObjectAsKey(): void
    {
        $map = new WeakMap();
        $object = new stdClass();
        $map[$object] = 'value';

        $this->assertTrue(
            self::isKnownToWeakMap($map, $object),
            sprintf(
                'PHP %s не разыменовывает референс, использованный как ключ WeakMap (баг GH-8995). '
                . 'Исправлено в PHP 8.0.22 и 8.1.9. На такой версии opis/closure v4 неработоспособен.',
                PHP_VERSION
            )
        );
    }

    /**
     * Уровень opis: ломается любой payload, содержащий хотя бы один объект.
     * Скаляры и строковые callable проходят и на битой версии PHP.
     */
    public function testOpisClosureRoundTripsPayloadWithObject(): void
    {
        $payload = new stdClass();
        $payload->value = 'preserved';

        $restored = \Opis\Closure\unserialize(\Opis\Closure\serialize($payload));

        $this->assertSame('preserved', $restored->value);
    }

    /**
     * Контракт библиотеки: SerializedCallback отдаёт в opis только замыкания
     * (SerializedCallback::__sleep), поэтому объекты-сервисы переживают
     * round-trip и на битой версии PHP, а замыкания — нет.
     */
    public function testSerializedCallbackRoundTripsClosure(): void
    {
        $callback = new SerializedCallback(static fn($value) => $value * 2);

        $restored = unserialize(serialize($callback));

        $this->assertSame(4, $restored(2));
    }

    /**
     * Массивный callable ломается по той же причине: __sleep конвертирует его
     * в замыкание перед сериализацией.
     */
    public function testSerializedCallbackRoundTripsArrayCallable(): void
    {
        $callback = new SerializedCallback([new Doubler(), 'double']);

        $restored = unserialize(serialize($callback));

        $this->assertSame(4, $restored(2));
    }

    /**
     * Последствие целиком, в форме реального продакшн-кода: объект-коллбэк
     * сериализуется нативно и работает, но внутри себя порождает замыкание
     * и отправляет его в дочерний процесс.
     *
     * Родитель отчитывается об успехе в любом случае — он лишь запускает
     * процесс в фоне и возвращает PID. Поэтому расхождение видно только по
     * побочному эффекту: работа не сделана.
     */
    public function testProcessExecutesClosureSpawnedByObjectCallback(): void
    {
        $outerCallback = new SpawnsClosureCallback(self::OUTPUT_FILE);

        $payload = $outerCallback();

        $this->assertInstanceOf(
            PayloadInterface::class,
            $payload,
            'Родитель должен вернуть payload с PID — он не ждёт дочерний процесс.'
        );

        $this->assertTrue(
            self::waitForFile(self::OUTPUT_FILE),
            sprintf(
                'Родитель сообщил об успехе (PID %s), но дочерний процесс не выполнил работу за %d с. '
                . 'На PHP %s замыкание не распаковывается в дочернем процессе (GH-8995), '
                . 'и задача молча не выполняется.',
                var_export($payload->getId(), true),
                self::CHILD_TIMEOUT_SEC,
                PHP_VERSION
            )
        );
    }

    private static function isKnownToWeakMap(WeakMap $map, object &$reference): bool
    {
        try {
            return isset($map[$reference]);
        } catch (\TypeError) {
            // На битой версии PHP возвращаем false, чтобы тест упал с осмысленным
            // сообщением, а не с сырым TypeError без объяснения.
            return false;
        }
    }

    private static function waitForFile(string $path): bool
    {
        $deadline = microtime(true) + self::CHILD_TIMEOUT_SEC;

        while (microtime(true) < $deadline) {
            clearstatcache(true, $path);
            if (is_file($path) && filesize($path) > 0) {
                return true;
            }
            usleep(100_000);
        }

        return false;
    }
}

/**
 * Повторяет форму Rollun\Service\Inventory\Callback\UpdateOldShipTemplatesCallback
 * из service-amazon-inventory: сам объект, а работу отдаёт замыканию в дочерний процесс.
 */
class SpawnsClosureCallback
{
    public function __construct(private string $file) {}

    public function __invoke(): mixed
    {
        $file = $this->file;

        $callback = new SerializedCallback(static function () use ($file): void {
            file_put_contents($file, "done\n", FILE_APPEND);
        });

        return (new Process($callback))();
    }

    public function __sleep(): array
    {
        return ['file'];
    }

    public function __wakeup(): void {}
}

class Doubler
{
    public function double(int $value): int
    {
        return $value * 2;
    }
}

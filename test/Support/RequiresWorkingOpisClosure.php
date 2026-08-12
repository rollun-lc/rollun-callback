<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Support;

/**
 * PHP bug GH-8995: a reference used as a WeakMap key is not dereferenced, which makes
 * \Opis\Closure\unserialize() throw a TypeError on any payload holding an object.
 * Broken in 8.0.0-8.0.21 and 8.1.0-8.1.8, fixed in 8.0.22 and 8.1.9.
 *
 * SerializedCallback hands closures to opis - and it converts string and array callables
 * into closures first - so those round-trips simply cannot work on an affected runtime.
 * Tests covering them are skipped there instead of being reported as library failures.
 */
trait RequiresWorkingOpisClosure
{
    protected static function phpBreaksOpisClosure(): bool
    {
        return PHP_VERSION_ID < 80022 || (PHP_VERSION_ID >= 80100 && PHP_VERSION_ID < 80109);
    }

    protected function skipIfPhpBreaksOpisClosure(): void
    {
        if (self::phpBreaksOpisClosure()) {
            $this->markTestSkipped(sprintf(
                'PHP %s is affected by GH-8995: opis/closure cannot unserialize a payload holding an object, '
                . 'so a serialized closure never survives the round-trip. Fixed in PHP 8.0.22 and 8.1.9.',
                PHP_VERSION
            ));
        }
    }
}

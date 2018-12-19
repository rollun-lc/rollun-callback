<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Promise\Interfaces;

interface WaitableInterface extends PayloadInterface
{
    /**
     * Waits until the promise completes if possible.
     *
     * Pass $unwrap as true to unwrap the result of the promise, either
     * returning the resolved value or throwing the rejected exception.
     *
     * If the promise cannot be waited on, then the promise will be rejected.
     *
     * @param bool $unwrap
     *
     * @return mixed
     * @throws \LogicException if the promise has no wait function or if the
     *                         promise does not settle after waiting.
     */
    public function wait($unwrap = true);

    /**
     * @return mixed
     */
    public function getState();
}

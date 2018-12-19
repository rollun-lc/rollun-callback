<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Promise\Interfaces;

use rollun\callback\Promise\PromiseException;

/**
 * Full Interface for Promise
 *
 * @category   async
 * @package    zaboy
 */
interface PromiseInterface extends WaitableInterface
{
    const FULFILLED = 'fulfilled';
    const REJECTED = 'rejected';
    const PENDING = 'pending';
    const DEPENDENT = 'dependent';

    /**
     * Appends fulfillment and rejection handlers to the promise, and returns
     * a new promise resolving to the return value of the called handler.
     *
     * @param callable $onFulfilled Invoked when the promise fulfills.
     * @param callable $onRejected  Invoked when the promise is rejected.
     *
     * @return PromiseInterface
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null);

    /**
     * Resolve the promise with the given value.
     *
     * @param mixed $value
     * @throws PromiseException if the promise is already resolved.
     */
    public function resolve($value);


    /**
     * Get the state of the promise ("pending", "rejected", "fulfilled" or "dependent").
     *
     * The three states can be checked against the constants defined on
     * PromiseInterface: PENDING, FULFILLED, and REJECTED.
     * If $dependentAsPending is false and promise was resolved by pending promise
     * method return DEPENDENT. Also we get dependent promise as result of then().
     *
     * @param bool $dependentAsPending true as default
     * @return string Status
     */
    public function getState($dependentAsPending = true);

    /**
     * Reject the promise with the given reason.
     *
     * @param mixed $reason
     * @throws PromiseException if the promise is already resolved.
     */
    public function reject($reason);
}

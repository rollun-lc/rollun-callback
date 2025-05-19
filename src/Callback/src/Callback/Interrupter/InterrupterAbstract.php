<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter;

use rollun\callback\Callback\SerializedCallback;

abstract class InterrupterAbstract implements InterrupterInterface
{
    public const INTERRUPTER_TYPE_KEY = 'interrupter_type';

    /**
     * @var SerializedCallback
     */
    protected $callback;

    /**
     * InterrupterAbstract constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->setCallback($callback);
    }

    /**
     * Wrap callable in SerializedCallback
     *
     * @param callable $callback
     */
    public function setCallback(callable $callback)
    {
        if (!$callback instanceof SerializedCallback) {
            $callback = new SerializedCallback($callback);
        }

        $this->callback = $callback;
    }

    /**
     * @return string
     */
    public function getInterrupterType()
    {
        return static::class;
    }
}

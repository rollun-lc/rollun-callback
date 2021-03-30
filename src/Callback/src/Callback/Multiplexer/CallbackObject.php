<?php

namespace rollun\callback\Callback\Multiplexer;

use InvalidArgumentException;

/**
 * Chose the name CallbackObject to avoid php keyword matches
 *
 * Data structure for callbacks for rollun\callback\Callback\Multiplexer
 *
 * Allows to name callbacks
 *
 * @package rollun\callback\Callback\Multiplexer
 */
class CallbackObject
{
    public const CALLBACK_KEY = 'callback';

    public const NAME_KEY = 'name';

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var string
     */
    private $name;

    public function __construct(callable $callback, string $name)
    {
        $this->callback = $callback;
        $this->name = $name;
    }

    public function __invoke($value = null)
    {
        return $this->runCallback($value);
    }

    public function runCallback($value = null)
    {
        return ($this->callback)($value);
    }

    /**
     * @param array $array
     * @return static
     * @throws InvalidArgumentException if array validation failed
     */
    public static function createFromArray(array $array): self
    {
        self::validateArray($array);
        return new self($array[self::CALLBACK_KEY], $array[self::NAME_KEY]);
    }

    /**
     * @param array $array
     * @throws InvalidArgumentException if validation failed
     */
    private static function validateArray(array $array)
    {
        if (!isset($array[self::NAME_KEY])) {
            throw new InvalidArgumentException('Array must have the key ' . self::CALLBACK_KEY);
        }
        if (!is_string($array[self::NAME_KEY])) {
            throw new InvalidArgumentException('The name must be a string');
        }

        if (!isset($array[self::CALLBACK_KEY])) {
            throw new InvalidArgumentException("Cant find callback for name {$array[self::NAME_KEY]}, because" .
                " array doesn't contain key " . self::CALLBACK_KEY);
        }
        if (!is_callable($array[self::CALLBACK_KEY])) {
            throw new InvalidArgumentException("Wrong callback with name {$array[self::NAME_KEY]}'! Callable expected.");
        }
    }
}
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

    private const CALLBACK_DEFAULT_NAME = 'Unknown';

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var string
     */
    private $name;

    public function __construct(callable $callback, ?string $name = null)
    {
        $this->callback = $callback;
        $this->name = empty($name) ? self::CALLBACK_DEFAULT_NAME : $name;
    }

    public function __invoke($value = null)
    {
        return $this->runCallback($value);
    }
    
    public function getName(): string
    {
        return $this->name;
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
        $name = $array[self::NAME_KEY] ?? null;
        return new self($array[self::CALLBACK_KEY], $name);
    }

    /**
     * @param array $array
     * @throws InvalidArgumentException if validation failed
     */
    private static function validateArray(array $array)
    {
        if (isset($array[self::NAME_KEY]) && !is_string($array[self::NAME_KEY])) {
            throw new InvalidArgumentException('The name must be a string');
        }

        if (!isset($array[self::CALLBACK_KEY])) {
            $additional = isset($array[self::NAME_KEY]) ? " for name {$array[self::NAME_KEY]}" : '';
            throw new InvalidArgumentException("Cant find callback{$additional}, because" .
                " array doesn't contain key " . self::CALLBACK_KEY);
        }
        if (!is_callable($array[self::CALLBACK_KEY])) {
            $additional = isset($array[self::NAME_KEY]) ? " with name {$array[self::NAME_KEY]}" : '';
            throw new InvalidArgumentException("Wrong callback{$additional}! Callable expected.");
        }
    }
}
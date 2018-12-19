<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter;

use InvalidArgumentException;
use Opis\Closure\SerializableClosure;
use ReflectionException;
use rollun\callback\Callback\SerializedCallback;
use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;

class Job
{
    /**
     * @var SerializedCallback
     */
    protected $callback;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var LifeCycleToken
     */
    protected $lifeCycleToken;

    /**
     * Job constructor.
     * @param callable $callback
     * @param $value
     * @param null $lifeCycleToken
     * @throws ReflectionException
     */
    public function __construct(callable $callback, $value, $lifeCycleToken = null)
    {
        InsideConstruct::setConstructParams(["lifeCycleToken" => LifeCycleToken::class]);

        if (!$callback instanceof SerializedCallback) {
            $callback = new SerializedCallback($callback);
        }

        $this->callback = $callback;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function serializeBase64(): string
    {
        $serializedParams = serialize($this);
        $base64string = base64_encode($serializedParams);

        return $base64string;
    }

    /**
     * @param $value
     * @return Job
     * @throws InvalidArgumentException
     */
    public static function unserializeBase64($value): Job
    {
        $job = unserialize(base64_decode($value, true));

        if (!$job instanceof Job) {
            throw new InvalidArgumentException(
                sprintf(
                    'instance of %s expected after unserializing',
                    is_object($job) ? get_class($job) : gettype($job)
                )
            );
        }

        return $job;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return SerializableClosure
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return LifeCycleToken
     */
    public function getLifeCycleToken()
    {
        return $this->lifeCycleToken;
    }
}

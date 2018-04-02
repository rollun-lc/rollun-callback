<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Callback\Interruptor;

use Opis\Closure\SerializableClosure;
use rollun\callback\Callback\CallbackException;
use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;

class Job
{
    protected $callback;

    protected $value;

    /**
     * @var LifeCycleToken
     */
    protected $lifeCycleToken;

    public function __construct(callable $callback, $value, $lifeCycleToken = null)
    {
        InsideConstruct::setConstructParams(["lifeCycleToken" => LifeCycleToken::class]);
        if (!is_callable($callback)) {
            throw new CallbackException('Callback is not callable');
        }
        if ($callback instanceof \Closure) {
            $callback = new SerializableClosure($callback);
        }
        $this->callback = $callback;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function serializeBase64()
    {
        $serializedParams = serialize($this);
        $base64string = base64_encode($serializedParams);
        return $base64string;
    }

    /**
     * @param string $value
     * @return Job
     */
    public static function unserializeBase64($value)
    {
        return unserialize(base64_decode($value, true));
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return callable|SerializableClosure
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

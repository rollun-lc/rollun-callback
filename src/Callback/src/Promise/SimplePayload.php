<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Promise;

use rollun\callback\Promise\Interfaces\PayloadInterface;

class SimplePayload implements PayloadInterface
{
    protected $id;

    protected $payload;

    public function __construct(string $id = null, array $payload = [])
    {
        $this->id = $id;
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @inheritdoc
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }
}

<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Promise\Interfaces;

interface PayloadInterface
{
    /**
     * Return unique id of payload
     *
     * @return mixed
     */
    public function getId(): string;

    /**
     * @return array
     */
    public function getPayload(): array;

    /**
     * @param $payload
     * @return array
     */
    public function setPayload(array $payload);
}

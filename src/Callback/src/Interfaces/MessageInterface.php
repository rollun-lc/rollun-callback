<?php
declare(strict_types=1);

namespace rollun\callback\Interfaces;

/**
 * Interface MessageInterface
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
interface MessageInterface
{
    /**
     * @return string
     */
    public function getLevel(): string;

    /**
     * @return string
     */
    public function getText(): string;
}



<?php
declare(strict_types=1);

namespace rollun\callback\Interfaces;

/**
 * Interface TaskUpdatedInterface
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
interface TaskUpdatedInterface
{
    /**
     * @return OkInterface
     */
    public function getMeta(): OkInterface;

    /**
     * @return MessageInterface[]|null
     */
    public function getMessages(): ?array;
}



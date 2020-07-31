<?php
declare(strict_types=1);

namespace rollun\callback\Interfaces;

/**
 * Interface TaskInterface
 *
 * @author r.ratsun <r.ratsun.rolun@gmail.com>
 */
interface TaskInterface
{
    /**
     * @return array|null
     */
    public function getData(): ?array;

    /**
     * @return BaseTaskMetaInterface
     */
    public function getMeta(): BaseTaskMetaInterface;

    /**
     * @return MessageInterface[]|null
     */
    public function getMessages(): ?array;
}



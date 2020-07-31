<?php
declare(strict_types=1);

namespace rollun\callback\Interfaces;

/**
 * Interface TasksInterface
 *
 * @author r.ratsun <r.ratsun.rolun@gmail.com>
 */
interface TasksInterface
{
    /**
     * @return BaseTaskMetaInterface[]|null
     */
    public function getData(): ?array;

    /**
     * @return ExpandedTaskMetaInterface
     */
    public function getMeta(): ExpandedTaskMetaInterface;

    /**
     * @return MessageInterface[]|null
     */
    public function getMessages(): ?array;
}



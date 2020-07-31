<?php
declare(strict_types=1);

namespace rollun\callback\Interfaces;

/**
 * Interface CreateTaskInterface
 *
 * @author r.ratsun <r.ratsun.rolun@gmail.com>
 */
interface CreateTaskInterface
{
    /**
     * @return array
     */
    public function getTaskPayload(): array;

    /**
     * @return string
     */
    public function getTaskId(): string;
}



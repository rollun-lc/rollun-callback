<?php
declare(strict_types=1);

namespace rollun\callback\Interfaces;

/**
 * Interface ProgressInterface
 *
 * @author r.ratsun <r.ratsun.rolun@gmail.com>
 */
interface ProgressInterface
{
    /**
     * @return string[]
     */
    public function getDefaultStatuses(): array;

    /**
     * @return string
     */
    public function getCurrent(): string;
}



<?php
declare(strict_types=1);

namespace rollun\callback\Interfaces;

/**
 * Interface ExpandedTaskMetaInterface
 *
 * @author r.ratsun <r.ratsun.rolun@gmail.com>
 */
interface ExpandedTaskMetaInterface
{
    /**
     * @return bool
     */
    public function isOk(): bool;

    /**
     * @return int
     */
    public function getTimeout(): int;

    /**
     * @return ProgressInterface|null
     */
    public function getProgress(): ?ProgressInterface;
}



<?php
declare(strict_types=1);

namespace rollun\callback\Interfaces;

/**
 * Interface BaseTaskMetaInterface
 *
 * @author r.ratsun <r.ratsun.rolun@gmail.com>
 */
interface BaseTaskMetaInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime;
}



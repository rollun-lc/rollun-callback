<?php
declare(strict_types=1);

namespace rollun\callback\Interfaces;

/**
 * Interface LongRunningTaskApiInterface
 *
 * @author r.ratsun <r.ratsun.rolun@gmail.com>
 */
interface LongRunningTaskApiInterface
{
    /**
     * @param int       $limit
     * @param int       $offset
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param string[]  $status
     *
     * @return TasksInterface
     */
    public function getTasks(int $limit = 20, int $offset = 0, \DateTime $startDate = null, \DateTime $endDate = null, array $status = null): TasksInterface;

    /**
     * @param string $id
     *
     * @return TaskInterface
     */
    public function getTask(string $id): TaskInterface;

    /**
     * @param CreateTaskInterface $createTask
     *
     * @return TaskUpdatedInterface
     */
    public function createTask(CreateTaskInterface $createTask): TaskUpdatedInterface;

    /**
     * @param string $id
     *
     * @return TaskUpdatedInterface
     */
    public function cancelTask(string $id): TaskUpdatedInterface;

    /**
     * @param string $id
     *
     * @return TaskUpdatedInterface
     */
    public function deleteTask(string $id): TaskUpdatedInterface;
}

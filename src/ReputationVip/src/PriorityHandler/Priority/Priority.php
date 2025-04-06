<?php

namespace ReputationVIP\QueueClient\PriorityHandler\Priority;

use ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface;

class Priority
{
    /**
     * @param string $name
     * @param int $level
     */
    public function __construct(private $name, private $level, private ?\ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface $priorityHandler = null)
    {
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }


    /**
     * @return PriorityHandlerInterface
     */
    public function getPriorityHandler() {
        return $this->priorityHandler;
    }

    /**
     * @param PriorityHandlerInterface $priorityHandler
     * @return $this
     */
    public function setPriorityHandler(PriorityHandlerInterface $priorityHandler) {
        $this->priorityHandler = $priorityHandler;

        return $this;
    }

    /**
     * @return Priority
     */
    public function next()
    {
        if (null === $this->priorityHandler) {
            return $this;
        }

        return $this->priorityHandler->getAfter($this);
    }

    /**
     * @return Priority
     */
    public function prev()
    {
        if (null === $this->priorityHandler) {
            return $this;
        }

        return $this->priorityHandler->getBefore($this);
    }
}
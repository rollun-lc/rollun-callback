<?php
declare(strict_types=1);

namespace rollun\callback\Callback\HealthChecker\Validator;

use Zend\Validator\ValidatorInterface;

/**
 * Class AbstractValidator
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @inheritDoc
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param bool $isValid
     *
     * @return array
     */
    public function getMetricDataForPrometheus(bool $isValid): array
    {
        return [];
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    protected function addMessage(string $message): AbstractValidator
    {
        $this->messages[] = $message;

        return $this;
    }
}

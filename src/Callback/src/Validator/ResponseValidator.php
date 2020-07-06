<?php
declare(strict_types=1);

namespace rollun\callback\Validator;

use Zend\Stdlib\ResponseInterface;
use Zend\Validator\ValidatorInterface;

/**
 * Class ResponseValidator
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class ResponseValidator implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        if (!$value instanceof ResponseInterface) {
            throw new \InvalidArgumentException('Implement of ' . ResponseInterface::class . ' expected');
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMessages()
    {
        return [];
    }
}

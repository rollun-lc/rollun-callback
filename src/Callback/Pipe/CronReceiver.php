<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.17
 * Time: 17:10
 */

namespace rollun\callback\Callback\Pipe;

use Zend\Stratigility\MiddlewarePipe;

class CronReceiver extends MiddlewarePipe
{

    /**
     *
     * @param array $middlewares
     */
    public function __construct($middlewares)
    {
        parent::__construct();
        foreach ($middlewares as $middleware) {
            $this->pipe($middleware);
        }
    }

}
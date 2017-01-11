<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.01.17
 * Time: 12:48
 */

namespace rollun\callback\Callback\Pipe;

use Zend\Stratigility\MiddlewarePipe;

class HttpReceiver extends MiddlewarePipe
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

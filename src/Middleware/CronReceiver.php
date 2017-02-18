<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.17
 * Time: 17:14
 */

namespace rollun\callback\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\callback\Callback\Interruptor\Multiplexer;
use rollun\callback\Callback\Interruptor\CronManager;
use rollun\promise\Promise\Exception;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\MiddlewareInterface;

class CronReceiver implements MiddlewareInterface
{
    const KEY_SEC_MULTIPLEXER = 'secMultiplexor';

    const KEY_MIN_MULTIPLEXER = 'minMultiplexor';

    /** @var  Multiplexer */
    protected $secMultiplexor;

    /** @var  Multiplexer */
    protected $minMultiplexor;

    public function __construct(Multiplexer $secMultiplexor, Multiplexer $minMultiplexor)
    {
        $this->secMultiplexor = $secMultiplexor;
        $this->minMultiplexor = $minMultiplexor;
    }

    /**
     * Process an incoming request and/or response.
     *
     * Accepts a server-side request and a response instance, and does
     * something with them.
     *
     * If the response is not complete and/or further processing would not
     * interfere with the work done in the middleware, or if the middleware
     * wants to delegate to another process, it can use the `$out` callable
     * if present.
     *
     * If the middleware does not return a value, execution of the current
     * request is considered complete, and the response instance provided will
     * be considered the response to return.
     *
     * Alternately, the middleware may return a response instance.
     *
     * Often, middleware will `return $out();`, with the assumption that a
     * later middleware will return a response.
     *
     * @param Request $request
     * @param Response $response
     * @param null|callable $out
     * @return null|Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        try {
            $cronManager = new CronManager($this->secMultiplexor, $this->minMultiplexor);
            $cronManager("start");

            $request = $request->withAttribute('responseData', []);
            $request = $request->withAttribute('status', 200);
        } catch (\Exception $exception) {
            //add request status
            $request = $request->withAttribute('responseData', ['error' => $exception->getMessage()]);
            $request = $request->withAttribute('status', 500);
        }

        if (isset($out)) {
            return $out($request, $response);
        }

        return $response;
    }
}

<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use ReflectionException;
use rollun\dic\InsideConstruct;
use rollun\callback\Callback\Interrupter\InterrupterInterface;
use rollun\logger\LifeCycleToken;
use rollun\utils\Json\Exception;
use rollun\utils\Json\Serializer;
use Laminas\Http\Client;
use Laminas\Http\Response;

class Http
{
    /**
     * @var string 'http://example.org'
     */
    protected $url;

    /**
     * @var string 'mylogin'
     * @see https://en.wikipedia.org/wiki/Basic_access_authentication
     */
    protected $login;

    /**
     * @var string 'kjfgn&56Ykjfnd'
     * @see https://en.wikipedia.org/wiki/Basic_access_authentication
     */
    protected $password;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var string
     */
    protected $method = 'POST';

    /**
     * @const array Allowed methods
     */
    public const ALLOWED_METHODS  = ['GET','POST','PUT','PATCH','DELETE','HEAD'];

    protected const SUPPORTED_KEYS = [
        'maxredirects',
        'useragent',
        'timeout',
    ];

    /**
     * @var LifeCycleToken
     */
    protected $lifeCycleToken;

    /**
     * HttpClient constructor.
     * @param $url
     * @param array $options
     * @param LifeCycleToken|null $lifeCycleToken
     * @throws ReflectionException
     */
    public function __construct($url, array $options = [], LifeCycleToken $lifeCycleToken = null)
    {
        InsideConstruct::setConstructParams(["lifeCycleToken" => LifeCycleToken::class]);
        $this->url = rtrim(trim($url), '/');

        if (isset($options['login']) && isset($options['password'])) {
            $this->login = $options['login'];
            $this->password = $options['password'];
        }

        if (isset($options['method']) && in_array($options['method'], self::ALLOWED_METHODS, true)) {
            $this->method = $options['method'];
        }

        $this->options = array_intersect_key($options, array_flip(static::SUPPORTED_KEYS));
    }

    /**
     * @param null|mixed $value
     * @return Client
     */
    protected function createHttpClient($value = null): Client
    {
        $httpClient = new Client($this->url, $this->options);

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        $headers['APP_ENV'] = getenv('APP_ENV');
        $headers['LifeCycleToken'] = $this->lifeCycleToken->serialize();

        $httpClient->setHeaders($headers);

        if (isset($this->login) && isset($this->password)) {
            $httpClient->setAuth($this->login, $this->password);
        }

        $httpClient->setMethod($this->method);
        $httpClient->setRawBody(Serializer::jsonSerialize($value));

        // TODO add tests
        if ($this->method === 'GET') {
            $httpClient->setParameterGet($value);
        }

        return $httpClient;
    }

    /**
     * @param null $value
     * @return array|mixed
     * @throws Exception
     */
    public function __invoke($value = null)
    {
        $client = $this->createHttpClient($value);
        $response = $client->send();

        if ($response->isSuccess()) {
            $payload = Serializer::jsonUnserialize($response->getBody());
        } else {
            $payload = [
                'error' => $response->getReasonPhrase(),
                'status' => $response->getStatusCode(),
                'message' => $response->getBody(),
            ];
        }

        return $payload;
    }

    /**
     * @param Response $response
     * @return bool
     */
    public function isResponseAcceptable($response)
    {
        return $response->getStatusCode() == 202 || $response->getStatusCode() == 200;
    }
}

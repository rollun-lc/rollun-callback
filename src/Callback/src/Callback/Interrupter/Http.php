<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter;

use InvalidArgumentException;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;
use rollun\utils\Json\Serializer;
use Zend\Http\Client;

class Http implements InterrupterInterface
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
     * @var LifeCycleToken
     */
    private $lifeCycleToken;

    /**
     * HttpClient constructor.
     * @param $url
     * @param array $options
     * @param LifeCycleToken|null $lifeCycleToken
     * @throws \ReflectionException
     */
    public function __construct($url, array $options = [], LifeCycleToken $lifeCycleToken = null)
    {
        InsideConstruct::setConstructParams(["lifeCycleToken" => LifeCycleToken::class]);
        $this->url = rtrim(trim($url), '/');

        if (isset($options['login']) && isset($options['password'])) {
            $this->login = $options['login'];
            $this->password = $options['password'];
        }

        $supportedKeys = [
            'maxredirects',
            'useragent',
            'timeout',
        ];
        $this->options = array_intersect_key($options, array_flip($supportedKeys));
    }

    /**
     * @param array $value
     * @return Client
     */
    protected function initHttpClient(array $value = [])
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

        $httpClient->setMethod('POST');
        $httpClient->setParameterPost($value);

        return $httpClient;
    }

    /**
     * @param $value
     * @return PayloadInterface
     * array contains field
     */
    public function __invoke($value): PayloadInterface
    {
        $client = $this->initHttpClient($value);
        $response = $client->send();

        if ($response->isOk()) {
            $payload = Serializer::jsonUnserialize($response->getBody());

            if (!$payload instanceof PayloadInterface) {
                throw new InvalidArgumentException(
                    sprintf('instance of %s expected after unserializing', PayloadInterface::class)
                );
            }
        } else {
            $payload = new SimplePayload(null, [
                'error' => $response->getReasonPhrase(),
                'status' => $response->getStatusCode(),
                'message' => $response->getBody(),
            ]);
        }

        return $payload;
    }
}

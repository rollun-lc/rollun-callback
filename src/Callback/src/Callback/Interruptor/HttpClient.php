<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 02.05.17
 * Time: 17:55
 */

namespace rollun\callback\Callback\Interruptor;


use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;
use rollun\utils\Json\Serializer;
use Zend\Http\Client;

class HttpClient implements InterruptorInterface
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
     *
     * @param string $url 'http://example.org'
     * @param array $options
     * @param LifeCycleToken $lifeCycleToken
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
     *
     * @param string 'GET' 'HEAD' 'POST' 'PUT' 'DELETE';
     * @param Query $query
     * @param int|string $id
     * @param bool see $ifMatch $rewriteIfExist and $createIfAbsent in {@see DataStoreAbstract}
     * @return Client
     */
    protected function initHttpClient(array $value = [])
    {

        $httpClient = new Client($this->url, $this->options);
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        $headers['APP_ENV'] = constant('APP_ENV');
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
     * @return array
     * array contains field
     *
     */
    public function __invoke($value)
    {
        $client = $this->initHttpClient($value);
        $response = $client->send();
        if ($response->isOk()) {
            $result = Serializer::jsonUnserialize($response->getBody());
        } else {
            $result = [
                'error' => $response->getReasonPhrase(),
                'status' => $response->getStatusCode(),
                'message' => $response->getBody()
            ];
        }
        return $result;
    }
}

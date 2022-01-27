<?php

namespace rollun\callback\Callback;

use Psr\Http\Message\RequestInterface;
use rollun\utils\Json\Serializer;
use Laminas\Http\Client;
use Laminas\Http\Headers;

class Proxy extends Http
{
    /**
     * @var RequestInterface
     */
    protected $request;

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    protected function createHttpClient($value = null): Client
    {
        $httpClient = new Client($this->url, $this->options);

        $headers = $this->prepareHeaders();
        $httpClient->setHeaders($headers);

        if (isset($this->login) && isset($this->password)) {
            $httpClient->setAuth($this->login, $this->password);
        }

        $httpClient->setMethod($this->method);

        if ($this->method === 'POST') {
            $httpClient->setRawBody(Serializer::jsonSerialize($value));
        } elseif ($this->method === 'GET') {
            $httpClient->setParameterGet($value);
        }

        return $httpClient;
    }

    protected function prepareHeaders()
    {
        $excluded = [
            'host',
            'content-length'
        ];
        $headers = new Headers();
        foreach ($this->request->getHeaders() as $key => $header) {
            $key = strtolower($key);
            if (!in_array($key, $excluded)) {
                $headers->addHeaderLine($key, $header);
            }
        }
        $headers->addHeaders([
            'APP_ENV' => getenv('APP_ENV'),
            'LifeCycleToken' => $this->lifeCycleToken->serialize(),
        ]);

        return $headers;
    }
}
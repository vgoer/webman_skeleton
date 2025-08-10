<?php

namespace utils;

use support\Log;

class HttpClient
{

    /**
     * @var array 默认参数
     */
    protected $defaultParams = [];

    /**
     * @var array 默认请求头
     */
    protected $defaultHeaders = [];

    /**
     * @var array 默认请求参数
     */
    protected $defaultOptions = [];

    /**
     * @param array $defaultOptions
     */
    public function setDefaultOptions(array $defaultOptions): void
    {
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * 获取GuzzleHttpClient实例
     * @return \GuzzleHttp\Client
     */
    protected function client()
    {
        return new \GuzzleHttp\Client([
            'base_uri' => property_exists($this, 'baseUri') ? $this->baseUri : '',
        ]);
    }

    /**
     * 发送get请求
     * @param $urlPath
     * @param array $params
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpGetRequest($urlPath, $params = [], $headers = [])
    {
        $options = [
            'query' => !empty($params) ? array_merge($this->defaultParams, $params) : $this->defaultParams,
            'headers' => !empty($headers) ? array_merge($this->defaultHeaders, $headers) : $this->defaultHeaders,
        ];
        return $this->request($urlPath, $options, 'GET');
    }

    /**
     * 发送post请求
     * @param $urlPath
     * @param array $params
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpPostRequest($urlPath, $params = [], $headers = [])
    {
        $options = [
            'form_params' => !empty($params) ? array_merge($this->defaultParams, $params) : $this->defaultParams,
            'headers' => !empty($headers) ? array_merge($this->defaultHeaders, $headers) : $this->defaultHeaders,
        ];
        return $this->request($urlPath, $options, 'POST');
    }

    /**
     * 发送post请求 json参数
     * @param $urlPath
     * @param array $params
     * @param array $headers
     * @return mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpPostJsonRequest($urlPath, $params = [], $headers = [])
    {
        $options = [
            'json' => !empty($params) ? array_merge($this->defaultParams, $params) : $this->defaultParams,
            'headers' => !empty($headers) ? array_merge($this->defaultHeaders, $headers) : $this->defaultHeaders,
        ];
        return $this->request($urlPath, $options, 'POST');
    }

    /**
     * 传入body请求
     * @param $urlPath
     * @param $body
     * @param array $headers
     * @return mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpPostBodyRequest($urlPath, $body, $headers = [])
    {
        $options = [
            'body' => $body,
            'headers' => !empty($headers) ? array_merge($this->defaultHeaders, $headers) : $this->defaultHeaders,
        ];
        return $this->request($urlPath, $options, 'POST');
    }

    /**
     * 发出请求
     * @param $urlPath
     * @param $params
     * @param string $method
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request($urlPath, $params, $method = 'GET')
    {
        $options = $this->defaultOptions;
        foreach ($params as $paramName => $param) {
            if (isset($options[$paramName])) {
                $options[$paramName] = array_merge($options[$paramName], $param);
            } else {
                $options[$paramName] = $param;
            }
        }
        Log::info('client_request:', compact('urlPath', 'options', 'method'));
        $response = $this->client()->request($method, $urlPath, $options);
        if ($response->getStatusCode() !== 200) {
            Log::error('client_request_fail:', compact('urlPath', 'options'));
            throw new \HttpException('请求失败');
        }
        $body = $response->getBody();
        $content = json_decode((string)$body, true);
        if (empty($content)) {
            $content = $body;
            Log::info('client_response_header', [$response->getHeaders()]);
        } else {
//            Log::info('client_response_body:', [$response->getHeaders(), $content]);
        }
        return $content;
    }

    /**
     * 设置默认参数
     * @param array $params
     */
    public function setDefaultParams(array $params)
    {
        $this->defaultParams = array_merge($this->defaultParams, $params);
    }

    /**
     * 设置默认请求头
     * @param array $headers
     */
    public function setDefaultHeaders(array $headers)
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
    }

}
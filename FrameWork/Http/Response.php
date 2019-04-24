<?php

/**
 * HTTP响应
 */

namespace FrameWork\Http;

class Response
{

    protected $protocolVersion = "1.1";
    protected $statusCode = 200;
    protected $headers = '';
    protected $body = '';

    /**
     * 设置状态码
     */
    public function setStatusCode($statusCode)
    {

        if (!is_int($statusCode) || $statusCode < 100 || $statusCode > 599) {
            return false;
        }

        $clone = clone $this;
        $clone->statusCode = $statusCode;

        return $clone;
    }

    /**
     * 设置header头
     */
    public function setHeader($key = '', $value = '')
    {
        $clone = clone $this;
        $clone->headers[$key] = $value;

        return $clone;
    }

    /**
     * 设置消息体
     */
    public function setBody($bodyContent = '')
    {
        $clone = clone $this;
        $clone->body = $bodyContent;

        return $clone;
    }

    /**
     * 设置协议类型
     */
    public function setProtocolVersion($protocolVersion = "1.1")
    {
        $clone = clone $this;
        $clone->protocolVersion = $protocolVersion;

        return $clone;
    }

    /**
     * 获取header头
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * 获取状态码
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * 获取协议类型
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * 获取消息体
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * 返回
     */
    public function respond($data = '')
    {

        header(sprintf('HTTP/%s %s', $this->getProtocolVersion(), $this->getStatusCode()));

        if (!empty($this->getHeaders())) {
            foreach ($this->getHeaders() as $key => $value) {
                header(sprintf('%s: %s', $key, $value), false);
            }
        }


        $output = $this->getBody();

        $output .= json_encode($data);

        echo $output;
    }

}

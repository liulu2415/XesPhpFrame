<?php

/**
 * HTTP响应
 */

namespace FrameWork\Http;

class Response
{

    protected $httpVersion = "1.1";
    protected $code = 200;
    protected $headers = '';
    protected $body = '';

    public function __construct($code = 200, $headers = [], $body = '')
    {
        $this->headers = $headers;
        $this->code = $code;
        $this->body = $body;
    }

    /**
     * 设置状态码
     */
    public function setCode($code)
    {

        if (!is_int($code) || $code < 100 || $code > 599) {
            return false;
        }

        $clone = clone $this;
        $clone->code = $code;

        return $clone;
    }

    /**
     * 设置header头
     */
    public function setHeader($headers = '')
    {
        $headers = explode(',', $headers);

        $clone = clone $this;
        $clone->headers = $headers;

        return $clone;
    }

    /**
     * 返回
     */
    public function write($data)
    {
        $output = sprintf('HTTP/%s %s', $this->httpVersion, $this->code);

        foreach ($this->headers as $name => $values) {
            $output .= sprintf('%s: %s', $name, $values);
        }

        $output .= json_encode($this->body);

        echo $output;
        exit;
    }

}

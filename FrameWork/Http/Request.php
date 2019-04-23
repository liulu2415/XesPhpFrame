<?php

/**
 * HTTP请求
 */

namespace FrameWork\Http;

class Request
{

    protected $server;

    public function __construct()
    {
        $this->server = $_SERVER;
    }

    /**
     * 获取uri
     */
    public function getUri()
    {
        return $this->server['REQUEST_URI'];
    }

}

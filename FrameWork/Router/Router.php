<?php

/**
 * 路由类
 */

namespace FrameWork\Router;

use FrameWork\Http\Request;
use FrameWork\Http\Response;

class Router
{

    private $request, $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function router()
    {

        $uri = $this->request->getUri();

        $className = 'App\Controller';

        $params = explode('/', trim($uri, '/'));
        $action = array_pop($params);

        foreach ($params as $val) {
            $className .= '\\' . ucfirst($val);
        }

        if (!class_exists($className)) {
            return $this->response->setStatusCode(404)->setHeader('Content-Type', 'text/html')->respond(['stat' => 0, 'data' => '404 Page Not Found']);
        }

        $obj = new $className();

        if (!method_exists($obj, $action)) {
            return $this->response->setStatusCode(404)->setHeader('Content-Type', 'text/html')->respond(['stat' => 0, 'data' => '404 Page Not Found']);
        }

        $data = $obj->$action();

        $this->response->write($data);
    }

}

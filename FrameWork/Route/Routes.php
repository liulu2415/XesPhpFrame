<?php

/**
 * 路由类
 */

namespace FrameWork\Route;

class Route
{

    private $request, $response;

    private function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function route($request = '', $response = '')
    {

        $uri = $request->getUri();

        $className = 'App\Controller';

        $params = explode('/', trim($uri, '/'));
        $action = array_pop($params);

        foreach ($params as $val) {
            $className .= '\\' . ucfirst($val);
        }

        if (!class_exists($className)) {
            return $response->setCode(404)->setHeader('Content-Type', 'text/html')->write(['stat' => 0, 'data' => '404 Page Not Found']);
        }

        $obj = new $className();

        if (!method_exists($obj, $_REQUEST['st'])) {
            return $response->setCode(404)->setHeader('Content-Type', 'text/html')->write(['stat' => 0, 'data' => '404 Page Not Found']);
        }

        if (!method_exists($obj, $action)) {
            return $response->setCode(404)->setHeader('Content-Type', 'text/html')->write(['stat' => 0, 'data' => '404 Page Not Found']);
        }

        $data = $obj->$action();

        $response->write($data);
    }

}

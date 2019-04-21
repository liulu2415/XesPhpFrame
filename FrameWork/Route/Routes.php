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
            return $response->withStatus(404)->withHeader('Content-Type', 'text/html')->withJson(['stat' => 0, 'data' => '404 Page Not Found']);
        }

        $obj = new $className();

        if (!method_exists($obj, $_REQUEST['st'])) {
            return $response->withStatus(404)->withHeader('Content-Type', 'text/html')->withJson(['stat' => 0, 'data' => '404 Page Not Found']);
        }

        if (!method_exists($obj, $action)) {
            return $response->withStatus(404)->withHeader('Content-Type', 'text/html')->withJson(['stat' => 0, 'data' => '404 Page Not Found']);
        }

        $data = $obj->$action();

        return $response->withJson($data);
    }

}

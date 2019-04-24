<?php

/**
 * 框架文件（未完待续）
 */

namespace FrameWork;

class Core
{

    private $container = [];

    /**
     * 框架初始化
     */
    public function init()
    {
        $this->container = new Container();
        $this->container->request = 'FrameWork\Http\Request';
        $this->container->response = 'FrameWork\Http\Response';
        $this->container->log = 'FrameWork\Log\Log';
        $this->container->Router = "FrameWork\Router\Router";
    }

    /**
     * 运行入口文件
     */
    public function run()
    {

        $this->init();
        $router = $this->container->Router;

        $router->Router();
    }

}

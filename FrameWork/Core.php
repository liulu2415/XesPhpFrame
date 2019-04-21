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
        $this->container->request = 'request';
        $this->container->response = 'response';
        $this->container->log = 'log';
    }

    /**
     * 运行入口文件
     */
    public function run()
    {

        $this->init();
        $route = $container->Route;

        $route->Route();
    }

}

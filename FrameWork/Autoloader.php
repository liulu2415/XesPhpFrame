<?php

/**
 * 自动加载类
 */

namespace FrameWork;

class Autoloader
{

    public static $loader;

    private function __construct()
    {
        spl_autoload_register(array(
            $this,
            'getLoader'
        ));
    }

    /**
     * 获取Autoloader单例
     */
    public static function init()
    {

        if (empty(self::$loader)) {
            self::$loader = new self();
        }

        return self::$loader;
    }

    /**
     * 类的自动加载方法
     */
    public function getLoader($path)
    {

        $filePath = ROOT_DIR . DIRECTORY_SEPARATOR . $path . '.php';

        if (is_file($filePath)) {
            require $filePath;
        }
    }

}

<?php

/**
 * 容器类
 */

namespace FrameWork;

use FrameWork\Http\Request;
use FrameWork\Http\Response;
use FrameWork\Router\Router;

class Container
{

    private $service = [];

    public function __set($key, $value)
    {
        $this->service[$key] = $value;
    }

    public function __get($key)
    {
        return $this->build($this->service[$key]);
    }

    /**
     * 解析依赖
     */
    public function build($className)
    {

        //反射
        $reflector = new \ReflectionClass($className);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Can't instantiate this.");
        }

        //获取构造函数
        $constructor = $reflector->getConstructor();

        // 若无构造函数，直接实例化并返回
        if (is_null($constructor)) {
            return new $className;
        }

        //获取构造函数参数
        $parameters = $constructor->getParameters();

        //解析构造函数的参数
        $dependencies = $this->getDependencies($parameters);

        //创建实例
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * 获取依赖关系
     */
    public function getDependencies($parameters)
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if (is_null($dependency) && $parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                $dependencies[] = $this->build($dependency->name);
            }
        }

        return $dependencies;
    }

}

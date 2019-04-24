<?php

/**
 * 控制器方法
 */

namespace Controller\Test;

use Controller\Controller;

class Test extends Controller
{

    /**
     * 测试
     */
    public function xesops()
    {
        return ['stat' => 1, 'data' => 'This is test data!'];
    }

}

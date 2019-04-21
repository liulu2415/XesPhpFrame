<?php

/**
 * 支付宝回调验证
 *
 * @author wyy
 */

namespace App\Controller\Web;

use App\Controller\Controller;

class AliPay extends Controller
{

    /**
     * @abstract 充值接口，用来处理各个接口返回值
     * @param void
     * @return void
     */
    public function pay()
    {
        file_put_contents('AliVerify.txt', json_encode($_REQUEST), 8);
        if (!empty($_REQUEST)) {
            $result = $this->api('Order', 'aliPayVerify', array('data' => $_REQUEST));
            if (!empty($result) && $result['stat'] == 1) {
                $this->rechargeSuccessResult();
                $res = $this->api('Order', 'aliPay', $_REQUEST);
                exit;
            }
        }
        $this->rechargeFailResult();
    }

    /**
     * @abstract 渠道充值成功后，返回个充值渠道信息，根据各个充值渠道协议返回相对应的字符串
     * @param void
     * @return vokd
     */
    protected function rechargeSuccessResult()
    {
        echo 'success';
    }

    /**
     * @abstract 渠道充值失败后，返回个充值渠道信息，根据各个充值渠道协议返回相对应的字符串
     * @param void
     * @return vokd
     */
    protected function rechargeFailResult()
    {
        echo 'fail';
    }

}

<?php

/**
 * Description of Jdpay
 *
 * @author user
 */

namespace App\Controller\Web;

use App\Controller\Controller;

class JdPay extends Controller
{

    /**
     * @abstract 充值接口，用来处理各个接口返回值
     * @param void
     * @return void
     */
    public function pay()
    {
        $reqData = file_get_contents("php://input");
        file_put_contents('JdVerify.txt', json_encode($reqData), 8);
        if (!empty($reqData)) {
            $result = $this->api('Order', 'jdPayVerify', array('data' => $reqData));
            if (!empty($result) && $result['stat'] == 1) {
                $this->rechargeSuccessResult();
                $tradeNum = $result['decryptData']['tradeNum'];
                $res = $this->api('Order', 'jdPay', array('out_trade_no' => $tradeNum));
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

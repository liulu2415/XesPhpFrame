<?php

/**
 * Description of WxPay
 *
 * @author user
 */

namespace App\Controller\Web;

use App\Controller\Controller;

class WxPay extends Controller
{

    /**
     * @abstract APP充值接口，用来处理各个接口返回值
     * @param void
     * @return void
     */
    public function appPay()
    {
        $postData = file_get_contents("php://input");

        file_put_contents('WxVerify.txt', json_encode($postData), 8);
        if (!empty($postData)) {
            $result = $this->api('Order', 'wxPayVerify', array('xml' => $postData, 'type' => 8));
            if (!empty($result) && $result['stat'] == 1) {
                $this->rechargeSuccessResult();
                $rechargeNum = $result['data'];
                $this->api('Order', 'wxPay', array('out_trade_no' => $rechargeNum));
                exit;
            }
        }
        $this->rechargeFailResult();
    }

    /**
     * @abstract web充值接口，用来处理各个接口返回值
     * @param void
     * @return void
     */
    public function webPay()
    {
        $postData = file_get_contents("php://input");

        if (!empty($postData)) {
            $result = $this->api('Order', 'wxPayVerify', array('xml' => $postData, 'type' => 7));
            if (!empty($result) && $result['stat'] == 1) {
                $this->rechargeSuccessResult();
                $rechargeNum = $result['data'];
                $this->api('Order', 'wxPay', array('out_trade_no' => $rechargeNum));
                exit;
            }
        }
        $this->rechargeFailResult();
    }

    /**
     * @abstract 微信新商户号充值接口，用来处理各个接口返回值
     * @param void
     * @return void
     */
    public function pay()
    {
        $postData = file_get_contents("php://input");

        file_put_contents('NewWxVerify.txt', json_encode($postData), 8);
        if (!empty($postData)) {
            $result = $this->api('Order', 'wxPayVerify', array('xml' => $postData, 'type' => 17));
            if (!empty($result) && $result['stat'] == 1) {
                $this->rechargeSuccessResult();
                $rechargeNum = $result['data'];
                $this->api('Order', 'wxPay', array('out_trade_no' => $rechargeNum));
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

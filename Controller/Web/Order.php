<?php

/**
 * Web端下单相关
 */

namespace App\Controller\Web;

use App\Controller\Controller;

class Order extends Controller
{

    public function __construct()
    {
        //验证登录
        $this->checkLogin();

        $this->ua = $this->isWeb();
        if ($this->ua == 1) {
            $this->view = 'Main';
            $this->layout = 'Main/Default';
        } else {
            $this->view = 'Touch';
            $this->layout = 'Touch/Default';
        }
    }

    private function _getParams($idEncode = '')
    {

        if (empty($idEncode)) {
            return false;
        }

        $params = json_decode(base64_decode($idEncode), true);

        $key = $params['key'];
        unset($params['key']);

        if ($params['stuId'] != $this->userId || md5(json_encode($params)) != $key) {
            return false;
        }

        $productTypeIds = $params['productIds'];

        if (empty($productTypeIds)) {
            return false;
        }

        $productTypeIdsArr = explode(',', $productTypeIds);

        $productIds = [];
        $productType = [];
        foreach ($productTypeIdsArr as $k => $v) {
            $cIdArr = explode('-', $v);
            if (empty($cIdArr[0])) {
                return false;
            }
            $productType[] = $cIdArr[0];
            array_shift($cIdArr);
            $productIds[] = implode('-', $cIdArr);
        }

        if (count(array_unique($productType)) != 1) {
            return false;
        }

        $this->params = array_merge($this->params, $params);
        if (empty($this->params['orderType'])) {
            if (!empty($params['grouponId']) || !empty($params['grouponOrderNum'])) {
                $this->params['orderType'] = 3;
            } elseif (!empty($params['presaleOrderNum'])) {
                $this->params['orderType'] = 5;
            } else {
                $this->params['orderType'] = 1;
            }
        }

        return [
            'productIds' => $productIds,
            'productType' => $productType[0],
            'grouponId' => !empty($params['grouponId']) ? $params['grouponId'] : 0,
            'grouponOrderNum' => !empty($params['grouponOrderNum']) ? $params['grouponOrderNum'] : '',
            'presaleOrderNum' => !empty($params['presaleOrderNum']) ? $params['presaleOrderNum'] : '',
            'promotionType' => !empty($params['promotionType']) ? $params['promotionType'] : 0,
            'promotionId' => !empty($params['promotionId']) ? $params['promotionId'] : 0,
            'isNewStu' => !empty($params['isNewStu']) ? $params['isNewStu'] : 0,
        ];
    }

    private function _getUrlParams()
    {
        $urlParams = '';

        $urlParams .=!empty($this->params['xeswx_sessid']) ? 'xeswx_sessid=' . $this->params['xeswx_sessid'] . '&' : '';
        $urlParams .=!empty($this->params['xeswx_sourceid']) ? 'xeswx_sourceid=' . $this->params['xeswx_sourceid'] . '&' : '';
        $urlParams .=!empty($this->params['xeswx_adsiteid']) ? 'xeswx_adsiteid=' . $this->params['xeswx_adsiteid'] . '&' : '';
        $urlParams .=!empty($this->params['xeswx_siteid']) ? 'xeswx_siteid=' . $this->params['xeswx_siteid'] . '&' : '';
        $urlParams .=!empty($this->session()->read('origin')) ? 'origin=' . $this->session()->read('origin') . '&' : '';
        $urlParams = rtrim($urlParams, '&');

        return $urlParams;
    }

    /**
     * 确认订单
     */
    public function confirm()
    {

        $idEncode = !empty($this->params['idEncode']) ? $this->params['idEncode'] : '';

        //参数解析
        $params = $this->_getParams($idEncode);
        if (empty($params)) {
            $this->set('error', '参数有误！');
            $this->render("/{$this->view}/Error");
            return;
        }

        //获取确认订单信息
        $data = [
            'stuId' => $this->userId,
            'uid' => $this->uid,
            'productIds' => $params['productIds'],
            'productType' => $params['productType'],
            'grouponId' => !empty($params['grouponId']) ? $params['grouponId'] : 0,
            'grouponOrderNum' => !empty($params['grouponOrderNum']) ? $params['grouponOrderNum'] : 0,
            'promotionType' => !empty($params['promotionType']) ? $params['promotionType'] : 0,
            'promotionId' => !empty($params['promotionId']) ? $params['promotionId'] : 0,
            'isNewStu' => !empty($params['isNewStu']) ? $params['isNewStu'] : 0,
            'presaleOrderNum' => !empty($params['presaleOrderNum']) ? $params['presaleOrderNum'] : '',
            'isOldOrderNum' => 0
        ];
        $confirmInfo = $this->api('Order', 'confirmOrder', $data);

        if (empty($confirmInfo) || empty($confirmInfo['stat'])) {
            $this->set('error', $confirmInfo['data']);
            $this->render("/{$this->view}/Error");
            return;
        }
        $confirmInfo = $confirmInfo['data'];

        //是否可以直接添加订单
        $isAddOrder = 1;
        if (!empty($confirmInfo['isSend']) || $confirmInfo['balance'] > 0) {
            $isAddOrder = 0;
        }

        if (!empty($confirmInfo['stuAdd'])) {
            foreach ($confirmInfo['stuAdd'] as &$stuAddInfo) {
                if (!empty($stuAddInfo['name'])) {
                    $stuAddInfo['name'] = htmlspecialchars($stuAddInfo['name'], ENT_QUOTES);
                }
                if (!empty($stuAddInfo['detail'])) {
                    $stuAddInfo['detail'] = htmlspecialchars($stuAddInfo['detail'], ENT_QUOTES);
                }
            }
        }

        //预售第二阶段获取付定金时的班级ID
        if (!empty($params['presaleOrderNum']) && !empty($confirmInfo['orderProductId'])) {
            foreach ($params['productIds'] as &$productIds) {
                $productIdArr = explode('-', $productIds);
                if (!empty($confirmInfo['orderProductId'][$productIdArr[0]])) {
                    $productIdArr[1] = $confirmInfo['orderProductId'][$productIdArr[0]];
                    $productIds = implode('-', $productIdArr);
                }
            }
        }

        //获取商品信息
        $productC = $this->loadComponent('Product\Product');
        $productObj = $productC->getProductObj($params['productType'], $params['productIds']);
        $productInfos = $productObj->getInfos();

        if (empty($productInfos) || empty($productInfos['stat'])) {
            return $productInfos;
        }

        //赠品信息
        $buyGiftsInfo = !empty($confirmInfo['buyGiftsInfo']) ? $confirmInfo['buyGiftsInfo'] : [];

        $orderDataC = $this->loadComponent('OrderData');
        $presaleStage = !empty($params['presaleOrderNum']) ? 2 : 1;
        //获取价格信息
        $priceInfo = $orderDataC->getPriceInfos($confirmInfo['promitionInfo'], $presaleStage);

        if (empty($priceInfo) || empty($priceInfo['stat'])) {
            $this->set('error', $priceInfo['data']);
            $this->render("/{$this->view}/Error");
            return;
        }
        $priceInfo = $priceInfo['data'];

        //获取促销类型
        $promotionTypeArr = $priceInfo['promotionAllType'];

        //判断订单类型
        $orderType = 1;
        if (in_array(12, $promotionTypeArr) || !empty($params['presaleOrderNum'])) {
            $orderType = 5;
        } elseif (!empty($params['grouponId']) || !empty($params['grouponOrderNum'])) {
            $orderType = 3;
        }

        //判断能否使用优惠券
        $isUseCoupon = 1;
        $notCouponPromotionType = config("CommonData\NotCouponPromotionType");
        if (!empty(array_intersect($promotionTypeArr, $notCouponPromotionType)) && empty($params['presaleOrderNum'])) {
            $isUseCoupon = 0;
        }

        //获取优惠券信息
        $couponParams = [];
        if (!empty($isUseCoupon) && empty($params['grouponId']) && empty($params['grouponOrderNum'])) {
            $couponParams = ['stuId' => $this->userId, 'productType' => $productObj->productType];
            foreach ($priceInfo['productPriceDetail'] as $productId => $productPriceInfo) {
                $couponParams['productInfo'][$productId] = $productPriceInfo['realPrice'];
            }

            $couponList = $this->api('CardCoupon', 'getStuValidCouponInfo', $couponParams);
            $couponList = !empty($couponList['stat']) ? $couponList['data'] : [];
            $isCanUseCoupon = array_unique(array_column($couponList, 'canUsed'));
            $this->set('isCanUseCoupon', $isCanUseCoupon);
            if ($isAddOrder == 1 && in_array(1, $isCanUseCoupon)) {
                $isAddOrder = 0;
            }

            if ($this->ua == 1) {
                $this->set('couponList', $couponList);
            } else {
                foreach ($couponList as $val) {
                    if (!empty($val['isDefault'])) {
                        $this->set('couponList', $val);
                    }
                }
            }
        }

        //获取直播广告信息[cookie里面取值]
        $liveAdvert = isset($_COOKIE['source']) ? $_COOKIE['source'] : '';
        if (!empty($liveAdvert)) {
            $liveAdvert = explode('&', $liveAdvert);
            if (!in_array(count($liveAdvert), [5, 6]) || $liveAdvert[0] != 'lectureadvert') {
                $liveAdvert = '';
            }
        }

        //跳过确认订单页
        if (!empty($isAddOrder)) {
            $orderAddUrl = config('CommonData\GeneralUrl.orderAddUrl');
            $urlParams = $this->_getUrlParams();
            if (!empty($urlParams)) {
                $this->redirect("{$orderAddUrl}?idEncode={$this->params['idEncode']}" . "&{$urlParams}");
                return;
            }
            $this->redirect("{$orderAddUrl}?idEncode={$this->params['idEncode']}");
            return;
        }

        $this->set('xeswxSessid', !empty($this->params['xeswx_sessid']) ? $this->params['xeswx_sessid'] : '');
        $this->set('xeswxSourceid', !empty($this->params['xeswx_sourceid']) ? $this->params['xeswx_sourceid'] : '');
        $this->set('xeswxAdsiteid', !empty($this->params['xeswx_adsiteid']) ? $this->params['xeswx_adsiteid'] : '');
        $this->set('xeswxSiteid', !empty($this->params['xeswx_siteid']) ? $this->params['xeswx_siteid'] : '');

        $this->session()->write('idEncode', $idEncode);

        $ver = config('CommonData\Ver.staticVer');
        $this->set('ver', $ver);
        $this->set('isUseCoupon', $isUseCoupon);
        $this->set('idEncode', $idEncode);
        $this->set('presaleStage', $presaleStage);
        $this->set('couponParams', $couponParams);
        $this->set('productInfo', $productInfos['data']['production']);
        $this->set('buyGiftsInfo', $buyGiftsInfo);
        $this->set('productInfoType', $productObj->productType);
        $this->set('orderType', $orderType);
        $this->set('grouponId', !empty($params['grouponId']) ? $params['grouponId'] : 0);
        $this->set('grouponOrderNum', !empty($params['grouponOrderNum']) ? $params['grouponOrderNum'] : '');
        $this->set('isSend', $confirmInfo['isSend']);
        $this->set('stuAdd', $confirmInfo['stuAdd']);
        $this->set('balance', $confirmInfo['balance']);
        $this->set('userId', $this->userId);
        $this->set('courseIds', implode(',', $params['productIds']));
        $this->set('liveAdvert', $liveAdvert);
        $this->set('priceInfo', $priceInfo);
        $this->render("/{$this->view}/Confirm");
    }

    /**
     * 添加订单
     */
    public function add()
    {
        $idEncode = !empty($this->params['idEncode']) ? $this->params['idEncode'] : '';

        $params = $this->_getParams($idEncode);

        if (empty($params)) {
            $this->set('error', '参数有误！');
            $this->render("/{$this->view}/Error");
            return;
        }

        $addId = 0;
        if ($this->ua == 1) {
            $addId = !empty($this->params['data']['addId']) ? $this->params['data']['addId'] : '';
        } else {
            $addId = !empty($this->params['addId']) ? $this->params['addId'] : '';
        }

        //热点
        $source = 0;
        if (!empty($_COOKIE['source'])) {
            $source = $_COOKIE['source'];
        }
        $xesId = !empty($_COOKIE['xesId']) ? $_COOKIE['xesId'] : '';
        $orderParams = [
            'productIds' => $params['productIds'],
            'productType' => $params['productType'],
            'stuId' => $this->userId,
            'uid' => $this->uid,
            'orderDevice' => $this->ua,
            'orderType' => !empty($this->params['orderType']) ? $this->params['orderType'] : 1,
            'stuCouponId' => !empty($this->params['stuCouponId'][0]) ? $this->params['stuCouponId'][0] : '',
            'addId' => $addId,
            'sourceId' => $source,
            'xesId' => $xesId,
            'grouponId' => !empty($this->params['grouponId']) ? $this->params['grouponId'] : 0,
            'grouponOrderNum' => !empty($this->params['grouponOrderNum']) ? $this->params['grouponOrderNum'] : '',
            'promotionType' => !empty($params['promotionType']) ? $params['promotionType'] : 0,
            'promotionId' => !empty($params['promotionId']) ? $params['promotionId'] : 0,
            'isNewStu' => !empty($params['isNewStu']) ? $params['isNewStu'] : 0,
            'presaleOrderNum' => !empty($params['presaleOrderNum']) ? $params['presaleOrderNum'] : '',
            'xeswxSourceId' => !empty($this->params['xeswx_sourceid']) ? $this->params['xeswx_sourceid'] : ''
        ];

        $result = $this->api('Order', 'addOrder', $orderParams);

        if (empty($result) || empty($result['stat'])) {
            $this->set('error', $result['data']);
            $this->render("/{$this->view}/Error");
            return;
        }

        if (!empty($result['data']['isExistOrder'])) {
            $this->set('orderNum', $result['data']['orderNum']);
            $this->set('productName', $result['data']['productName']);
            $this->render("/{$this->view}/ProductInOrder");
            return;
        }

        $grouponOrderNum = !empty($result['data']['grouponOrderNum']) ? $result['data']['grouponOrderNum'] : '';

        $this->session()->write('orderNum', $result['data']['orderNum']);
        $this->session()->write('grouponOrderNum', $grouponOrderNum);

        $signatureInfo = [
            'signature' => $this->signature,
            'data' => [
                'orderNum' => $result['data']['orderNum'],
                'grouponOrderNum' => $grouponOrderNum
            ]
        ];
        $this->api('Order', 'setOrderSignatureInfo', $signatureInfo);

        $isUseBalance = !empty($this->params['isUseBalance']) ? 1 : 0;
        $this->session()->write('isUseBalance', $isUseBalance);
        if (!empty($this->params['isUseBalance'])) {
            $payParams = [
                'stuId' => $this->userId,
                'uid' => $this->uid,
                'payDevice' => $this->ua,
                'payCode' => 301000,
                'orderNum' => $result['data']['orderNum'],
            ];

            $payResult = $this->api('Order', 'payOrder', $payParams);

            if (!empty($payResult['stat'])) {
                //支付成功跳转到支付成功页
                $completeUrl = config('CommonData\GeneralUrl.shoppingCompleteUrl');
                $urlParams = $this->_getUrlParams();
                if (!empty($urlParams)) {
                    $completeUrl .= '?' . $urlParams;
                }
                $this->redirect($completeUrl);
                return;
            }
        }

        $urlParams = $this->_getUrlParams();
        if (!empty($urlParams)) {
            return "<script> window.location.href='/Order/show?{$urlParams}'; </script>";
        }
        return "<script> window.location.href='/Order/show'; </script>";
    }

    /**
     * 订单展示
     */
    public function show()
    {
        $orderNum = !empty($this->params['orderNum']) ? $this->params['orderNum'] : $this->session()->read('orderNum');
        if (empty($orderNum)) {
            $this->set('error', '订单号为空！');
            $this->render("/{$this->view}/Error");
            return;
        }

        //是否是touch端微信浏览器
        $isWxTouch = 0;
        if ($this->ua == 3) {
            $isWxTouch = $this->isWx();
            if (!empty($isWxTouch)) {
                $openId = !empty($_COOKIE['xes_openid']) ? $_COOKIE['xes_openid'] : (!empty($_SESSION['xes_openid']) ? $_SESSION['xes_openid'] : '');
                if (empty($openId) || strpos($openId, 'oRN') === 0) {
                    $wxAuthorizeUrl = config('CommonData\GeneralUrl.wxAuthorizeUrl');
                    $orderShowUrl = config('CommonData\GeneralUrl.orderShowUrl');
                    $wxAuthorizeUrl .=!empty($this->params['orderNum']) ? base64_encode($orderShowUrl . '?orderNum=' . $this->params['orderNum']) : base64_encode($orderShowUrl);
                    $this->redirect($wxAuthorizeUrl);
                    return;
                }
            }
        }

        $this->session()->write('orderNum', $orderNum);
        $orderInfos = $this->api('Order', 'getOrderShowInfo', ['orderNum' => $orderNum]);
        if (empty($orderInfos) || empty($orderInfos['stat'])) {
            $this->set('error', '订单信息为空！');
            $this->render("/{$this->view}/Error");
            return;
        }

        $orderInfos = $orderInfos['data'];
        //订单过期时间
        $orderExpireTime = 900;

        $orderTime = $orderInfos['createTime'];
        $realPrice = $orderInfos['realPrice'];
        $status = $orderInfos['status'];
        if ($orderInfos['orderType'] == 5) {
            $orderTime = $orderInfos['presaleOrderInfo']['createTime'];
            $realPrice = $orderInfos['presaleOrderInfo']['realPrice'];
        }

        $expireTime = strtotime("+{$orderExpireTime} seconds", strtotime($orderTime));
        $nowTime = time();
        if ($nowTime >= $expireTime) {
            $this->set('error', '订单已过期...');
            $this->render("/{$this->view}/Error");
            return;
        }

        if ($status == 3) {
            $completeUrl = config('CommonData\GeneralUrl.shoppingCompleteUrl');
            $urlParams = $this->_getUrlParams();
            if (!empty($urlParams)) {
                $completeUrl .= '?' . $urlParams;
            }
            $this->redirect($completeUrl);
            return;
        }

        if ($status > 3 && $status != 6) {
            $this->set('error', '订单已被取消...');
            $this->render("/{$this->view}/Error");
            return;
        }

        if ($orderInfos['stuId'] != $this->userId) {
            $this->set('error', '此订单不属于此用户');
            $this->render("/{$this->view}/Error");
            return;
        }

        $isUseBalance = 0;
        $oldOrderNum = !empty($this->session()->read('orderNum')) ? $this->session()->read('orderNum') : '';
        if ($orderNum == $oldOrderNum) {
            $isUseBalance = !empty($this->session()->read('isUseBalance')) ? $this->session()->read('isUseBalance') : 0;
        }
        $this->session()->write('orderNum', $orderNum);
        $this->session()->write('isUseBalance', $isUseBalance);

        $balance = 0;
        if (!empty($isUseBalance)) {
            $result = $this->api('Order', 'getUserBalance', ['uid' => $this->uid]);
            if (empty($result) || empty($result['stat'])) {
                $this->set('error', '余额获取失败！');
                $this->render("/{$this->view}/Error");
                return;
            }
            $balance = $result['data'];
        }

        if (!empty($isUseBalance) && !empty($balance)) {
            $payParams = [
                'stuId' => $this->userId,
                'uid' => $this->uid,
                'payDevice' => $this->ua,
                'isUseBalance' => $isUseBalance,
                'payCode' => 301000,
                'ip' => $this->getCommon()->getIp(),
                'orderNum' => $orderNum,
            ];

            $result = $this->api('Order', 'payOrder', $payParams);

            if (!empty($result) && !empty($result['stat'])) {
                //支付成功跳转到支付成功页
                $completeUrl = config('CommonData\GeneralUrl.shoppingCompleteUrl');
                $urlParams = $this->_getUrlParams();
                if (!empty($urlParams)) {
                    $completeUrl .= '?' . $urlParams;
                }
                $this->redirect($completeUrl);
                return;
            }
        }


        $this->set('isWxTouch', $isWxTouch);

        //订单过期时间
        $this->set('expireTime', $expireTime - $nowTime);
        $this->set('orderInfos', $orderInfos);
        $this->set('realPrice', $realPrice);
        $this->set('balance', $balance);
        $this->set('isUseBalance', $isUseBalance);

        $ver = config('CommonData\Ver.staticVer');
        $this->set('ver', $ver);

        $this->render("/{$this->view}/Show");
    }

    /**
     * 生成第三方支付数据
     */
    public function pay()
    {
        $isPerformanceTest = $this->isPerformanceTest();

        if ($this->userId < 100000 && empty($isPerformanceTest)) {
            return json_encode(['sign' => 0, 'msg' => '网校测试账号请使用余额支付，余额不足请充值！']);
        }

        $orderNum = !empty($this->params['orderNum']) ? $this->params['orderNum'] : $this->session()->read('orderNum');

        if (empty($orderNum)) {
            return json_encode(['sign' => 0, 'msg' => '订单号为空！']);
        }
        $payTypeCode = !empty($this->params['payCode']) ? $this->params['payCode'] : 401001;

        $orderExpireTime = 900;
        $rechargeNumInfo = [];
        //支付宝扫码支付
        if ($payTypeCode == 401002) {
            $rechargeNumInfo = $this->session()->read('aliRechargeNumInfo');
        }

        //微信扫码支付
        if ($payTypeCode == 803000) {
            $rechargeNumInfo = $this->session()->read('wxRechargeNumInfo');
        }

        //京东扫码支付
        if ($payTypeCode == 110001) {
            $rechargeNumInfo = $this->session()->read('jdRechargeNumInfo');
        }


        if (!empty($rechargeNumInfo)) {
            $rechargeNumExpireTime = $rechargeNumInfo['time'] + $orderExpireTime;
            if ((time() < $rechargeNumExpireTime) && ($rechargeNumInfo['orderNum'] == $orderNum)) {
                return json_encode(['sign' => 1, 'msg' => $rechargeNumInfo['data']]);
            }
        }

        $openId = !empty($_COOKIE['xes_openid']) ? $_COOKIE['xes_openid'] : (!empty($this->session()->read('xes_openid')) ? $this->session()->read('xes_openid') : '');
        $payParams = [
            'stuId' => $this->userId,
            'uid' => $this->uid,
            'payDevice' => $this->ua,
            'isUseBalance' => !empty($this->params['isUseBalance']) ? $this->params['isUseBalance'] : 0,
            'payCode' => $payTypeCode,
            'ip' => $this->getCommon()->getIp(),
            'orderNum' => $orderNum,
            'defaultBank' => !empty($this->params['bank_id']) ? $this->params['bank_id'] : '',
            'openId' => $openId
        ];

        $result = $this->api('Order', 'payOrder', $payParams);

        if (empty($result) || empty($result['stat'])) {
            return json_encode(['sign' => 0, 'msg' => '生成支付链接失败！']);
        }

        $rechargeNumInfo = [
            'time' => time(),
            'data' => $result['data']['payData'],
            'orderNum' => $orderNum
        ];

        if ($payTypeCode == 401002) {
            $this->session()->write('aliRechargeNumInfo', $rechargeNumInfo);
        }

        if ($payTypeCode == 803000) {
            $this->session()->write('wxRechargeNumInfo', $rechargeNumInfo);
        }

        if ($payTypeCode == 110001) {
            $this->session()->write('jdRechargeNumInfo', $rechargeNumInfo);
        }

        return json_encode(['sign' => 1, 'msg' => $result['data']['payData']]);
    }

    /**
     * 生成支付二维码
     */
    public function createQrCode()
    {
        $data = '';
        $type = !empty($this->params['type']) ? $this->params['type'] : 1;
        if ($type == 1) {
            $data = $this->session()->read('aliRechargeNumInfo');
        } else if ($type == 2) {
            $data = $this->session()->read('wxRechargeNumInfo');
        } else if ($type == 3) {
            $data = $this->session()->read('jdRechargeNumInfo');
        }

        $qrCode = !empty($data['data']) ? $data['data'] : '';

        header("Content-type: image/png");
        $codeC = $this->loadComponent('CreateQrCode');
        $codeC->getQrCode($qrCode);
        exit;
    }

    /**
     * 获取订单状态
     */
    public function getOrderPayStatus()
    {

        $result = false;
        //订单号
        $orderNum = !empty($this->params['orderNum']) ? $this->params['orderNum'] : $this->session()->read('orderNum');

        if (!empty($orderNum)) {
            $data = $this->api('Order', 'getOrderPayStatus', ['orderNum' => $orderNum]);

            if (!empty($data) && !empty($data['stat']) && !empty($data['data'])) {
                $result = true;
            }
        }

        echo json_encode($result);
        exit;
    }

    /**
     * 重新支付
     */
    public function rePay()
    {

        $orderNum = !empty($this->params['orderNum']) ? $this->params['orderNum'] : $this->session()->read('orderNum');

        if (empty($orderNum)) {
            echo json_encode(['sign' => 1, 'msg' => "/"]);
            exit;
        }
        $ocenterUrl = config('CommonData\GeneralUrl.ocenterUrl');

        echo json_encode([
            'sign' => 1, 'msg' => $ocenterUrl . "/MyOrders/show"
        ]);
        exit;
    }

    /**
     * 生成支付链接入口
     */
    public function createTradeUrl()
    {
        if (empty($this->params['flag'])) {
            $this->render("Main/TradeUrl");
            return;
        }

        if (empty($this->params['productIds'])) {
            $this->set('error', '参数有误！');
            $this->render("/{$this->view}/Error");
        }
        $data['productIds'] = $this->params['productIds'];
        $data['stuId'] = $this->userId;
        $data['presaleOrderNum'] = !empty($this->params['presaleOrderNum']) ? $this->params['presaleOrderNum'] : '';
        $data['grouponId'] = !empty($this->params['grouponId']) ? $this->params['grouponId'] : 0;
        $data['grouponOrderNum'] = !empty($this->params['grouponOrderNum']) ? $this->params['grouponOrderNum'] : '';
        $data['promotionType'] = !empty($this->params['promotionType']) ? $this->params['promotionType'] : 0;
        $data['promotionId'] = !empty($this->params['promotionId']) ? $this->params['promotionId'] : 0;
        $data['key'] = md5(json_encode($data));

        $idEncode = base64_encode(json_encode($data));
        $url = '/Web/Order/confirm?idEncode=' . $idEncode;
        $this->redirect($url);
    }

}

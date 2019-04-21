<?php

/**
 * Description of Order
 *
 * @author user
 */

namespace App\Controller\Web;

use App\Controller\Controller;

class MyInfo extends Controller
{

    public function __construct()
    {
        $this->checkLogin();
    }

    /**
     *    用户的收获地址列表
     */
    public function ajaxGetStuAdds()
    {
        $this->autoRender = false;

        $result = $this->api('User', 'addresses', ['uids' => [$this->uid], 'info_type' => 3, 'is_normal' => 1]);

        if (!empty($result) || $result['stat'] == 1) {
            $stuAdd = $result['data'];
        }

        if ($result['stat'] == 0) {
            $data = [
                'sign' => 0,
                'data' => $result['data'],
            ];
            return json_encode($data);
        }

        if (!empty($result['data'][$this->uid])) {
            foreach ($result['data'][$this->uid] as &$stuAddInfo) {
                if (!empty($stuAddInfo['name'])) {
                    $stuAddInfo['name'] = htmlspecialchars($stuAddInfo['name'], ENT_QUOTES);
                }
                if (!empty($stuAddInfo['detail'])) {
                    $stuAddInfo['detail'] = htmlspecialchars($stuAddInfo['detail'], ENT_QUOTES);
                }
            }
        }

        $data = [
            'sign' => 1,
            'data' => !empty($result['data'][$this->uid]) ? $result['data'][$this->uid] : '',
        ];
        return json_encode($data);
    }

    /**
     * 保存用户收货地址
     */
    public function saveStuAdds()
    {
        $this->autoRender = false;

        $recipient = isset($this->params['realname']) ? trim($this->params['realname']) : '';
        $address = isset($this->params['address']) ? trim($this->params['address']) : '';

        if (!preg_match('/^[0-9a-zA-Z\x{4e00}-\x{9fa5}]+$/u', $recipient)) {
            $res = [
                'sign' => 0,
                'msg' => '收货人格式不正确！'
            ];
            return json_encode($res);
        }

        if (preg_match("/[`~!@#$^&*()=|{}':;'\\[\\]<>?~]/", $address)) {
            $res = [
                'sign' => 0,
                'msg' => '详细地址格式不正确，请重新填写！'
            ];
            return json_encode($res);
        }

        $data = [
            'id' => !empty($this->params['id']) ? $this->params['id'] : 0,
            'uid' => $this->uid,
            'user_id' => $this->userId,
            'name' => $recipient,
            'phone' => !empty($this->params['phone']) ? $this->params['phone'] : '',
            'province_id' => !empty($this->params['province_id']) ? $this->params['province_id'] : '',
            'city_id' => !empty($this->params['city_id']) ? $this->params['city_id'] : '',
            'county_id' => !empty($this->params['country_id']) ? $this->params['country_id'] : '',
            'detail' => $address,
        ];

        $result = [];
        if (empty($this->params['id'])) {
            $addressData = $this->api('User', 'addresses', ['uids' => [$this->uid], 'info_type' => 3]);

            if (empty($addressData) || empty($addressData['stat']) || (!empty($addressData['data'][$this->uid]) && count($addressData['data'][$this->uid]) >= 10)) {
                $data = [
                    'sign' => 0,
                    'msg' => '最多添加十条用户地址！'
                ];
                return json_encode($data);
            }

            $result = $this->api('User', 'addAddress', $data);
        } else {
            $result = $this->api('User', 'modAddress', $data);
        }

        if (empty($result) || empty($result['stat'])) {
            $data = [
                'sign' => 0,
                'msg' => $result['data'],
            ];
            return json_encode($data);
        }

        $data = [
            'sign' => 1,
            'type' => !empty($this->params['id']) ? 2 : 1,
            'addId' => $result['data']['id'],
            'province' => $result['data']['province_name'],
            'city' => $result['data']['city_name'],
            'country' => $result['data']['county_name'],
            'default' => empty($result['data']['is_default']) ? 0 : 1,
        ];
        return json_encode($data);
    }

    /**
     * 删除收货地址
     */
    public function delStuAddress()
    {
        $this->autoRender = false;

        $data = [
            'uid' => $this->uid,
            'user_id' => $this->userId,
            'id' => !empty($this->params['id']) ? $this->params['id'] : 0,
        ];

        $result = $this->api('User', 'rmAddress', $data);

        if ($result['stat'] == 0) {
            $data = [
                'sign' => 0,
                'msg' => $result['data'],
            ];
            return json_encode($data);
        }

        $data = [
            'sign' => 1,
            'msg' => 'ok',
        ];
        return json_encode($data);
    }

    /**
     * 获取用户未使用优惠券
     */
    public function ajaxGetStuCouponList()
    {
        $this->autoRender = false;
        $data = !empty($this->params['data']) ? $this->params['data'] : '';
        $couponParams = json_decode(base64_decode($data), true);

        $couponList = $this->api('CardCoupon', 'getStuValidCouponInfo', $couponParams);
        $couponList = !empty($couponList['stat']) ? $couponList['data'] : [];

        $result['available'] = [];
        $result['unavailable'] = [];
        foreach ($couponList as $key => $val) {
            if ($val['canUsed'] == 0) {
                $result['unavailable'][] = $val;
            } else {
                $result['available'][] = $val;
            }
        }
        if (empty($couponList)) {
            $data = [
                'sign' => 1,
                'data' => '',
            ];
            return json_encode($data);
        }

        $data = [
            'sign' => 1,
            'data' => $result,
        ];
        return json_encode($data);
    }

    /**
     * 设置用户默认地址
     */
    public function setDefaultAdds()
    {
        $this->autoRender = false;

        $data = [
            'id' => !empty($this->params['id']) ? $this->params['id'] : 0,
            'uid' => $this->uid,
            'user_id' => $this->userId,
            'is_default' => 1
        ];

        $result = [];

        $result = $this->api('User', 'modAddress', $data);
        if (empty($result) || empty($result['stat'])) {
            $data = [
                'sign' => 0,
                'msg' => $result['data'],
            ];
            return json_encode($data);
        }

        $data = [
            'sign' => 1,
            'msg' => 'ok'
        ];
        return json_encode($data);
    }

    /**
     * 获取实物分类
     */
    public function ajaxGetGoodsType()
    {
        $this->autoRender = false;

        $goodsTypeData = $this->api('Express', 'getGoodsType', []);
        if (empty($goodsTypeData) || empty($goodsTypeData['stat'])) {
            $data = [
                'sign' => 0,
                'msg' => $goodsTypeData['data'],
            ];
            return json_encode($data);
        }

        $result = [];
        $goodsTypeData = $goodsTypeData['data'];
        foreach ($goodsTypeData as $value) {
            if ($value['is_front_show'] == 1 && $value['type_status'] == 1) {
                $result[] = [
                    'name' => $value['name'],
                    'price' => $value['price'],
                ];
            }
        }

        $data = [
            'sign' => 1,
            'msg' => $result
        ];
        return json_encode($data);
    }

}

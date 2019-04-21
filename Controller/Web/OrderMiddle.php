<?php

/**
 * Description of Order
 *
 * @author user
 */

namespace App\Controller\Web;

use App\Controller\Controller;

class OrderMiddle extends Controller
{

    public function __construct()
    {
        $this->ua = $this->isWeb();
        if ($this->ua == 1) {
            $this->view = 'Main';
        } else {
            $this->view = 'Touch';
        }
    }

    /**
     * 快应用微信H5支付中间页
     */
    public function KyyWxPay()
    {
        if ($this->ua == 3) {
            $this->render("/{$this->view}/KyyWxPay");
        }
    }

}

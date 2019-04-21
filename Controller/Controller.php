<?php

namespace App\Controller;

use Interop\Container\ContainerInterface;
use Slim\Views\PhpRenderer;
use XesLib\Log\XesLog;
use XesLib\Validation\Validation;
use XesLib\Session\Session;
use XesLib\Network\Curl;
use XesLib\Common;
use XesSdk\SdkDispatch;
use XesSdk\NewApi;

class Controller
{

    static public $ci;
    protected $rules;   //参数验证规则
    protected $logger;  //日志对象
    protected $params;  //请求参数
    protected $renderData = [];  //render渲染数据
    static private $_compInstance = [];  //组件单例
    static private $_logInstance = [];  //业务日志单例
    static private $_sdkInstance = [];   //sdk单例
    static private $_sessionInstance = '';   //session单例
    static private $_commonInstance = '';   //common单例

    public function initialize()
    {

        $this->params = $_REQUEST;
    }

    /**
     * 加载组件
     *
     * @param type $name
     * @return type
     */
    protected function loadComponent($name)
    {

        if (empty(self::$_compInstance[$name]) || !(self::$_compInstance[$name] instanceof Component)) {
            $component = "App\\Component\\$name";
            self::$_compInstance[$name] = new $component(self::$ci);
        }

        return self::$_compInstance[$name];
    }

    /**
     * 加载日志
     *
     * @param type $name
     * @return
     */
    protected function logger($name = 'default')
    {

        if (empty(self::$_logInstance[$name]) || !(self::$_logInstance[$name] instanceof XesLog)) {
            $logConf = config('Log\\Log.' . $name);
            self::$_logInstance[$name] = new XesLog($logConf);
        }

        return self::$_logInstance[$name];
    }

    /**
     * 数据格式验证
     *
     * @param type $method
     * @return type
     */
    protected function validate($method)
    {

        $request = self::$ci->get('request');
        $valor = new Validation();
        return $valor->validateParams($request, $this->rules[$method]);
    }

    /**
     * 通过sdk请求api
     *
     * @param type $service
     * @param type $function
     * @param type $params
     * @return type
     */
    protected function api($service, $function, $params = array())
    {

        if (empty(self::$_sdkInstance[$service]) || !(self::$_sdkInstance[$service] instanceof SdkDispatch)) {
            $conf = config('Sdk\Sdk.' . $service);
            self::$_sdkInstance[$service] = new SdkDispatch($conf);
        }

        return self::$_sdkInstance[$service]->$service($function, $params);
    }

    /**
     * 通过sdk请求newapi
     *
     */
    protected function newApi($service, $function, $params = array(), $source = '')
    {
        if (empty(self::$_sdkInstance['NewApi']) || !(self::$_sdkInstance['NewApi'] instanceof NewApi)) {
            self::$_sdkInstance['NewApi'] = new NewApi();
        }
        return self::$_sdkInstance['NewApi']->$service($function, $params, $source);
    }

    /**
     * 获取session实例
     *
     * @return type
     */
    protected function session()
    {

        if (empty(self::$_sessionInstance) || !(self::$_sessionInstance instanceof Session)) {
            self::$_sessionInstance = new Session();
        }

        return self::$_sessionInstance;
    }

    /**
     * 获取公共基础类实例
     *
     * @return type
     */
    protected function getCommon()
    {

        if (empty(self::$_commonInstance) || !(self::$_commonInstance instanceof Common)) {
            self::$_commonInstance = new Common();
        }

        return self::$_commonInstance;
    }

    /**
     * Curl
     *
     * @param type $method
     * @return type
     */
    protected function curl($config, $type, $option = array(), $params = array())
    {

        $curlConf = config('Curl\Curl.' . $config);
        $logEnable = config('Config.settings')['curlLog'];
        if ($logEnable) {
            $curlConf['log'] = config('Log\\Log.curl');
        }

        if ($type == 1) {
            $data = Curl::get($curlConf, $option);
        } elseif ($type == 2) {
            $data = Curl::post($curlConf, $params, $option);
        }

        return $data;
    }

    /**
     * 跳转
     *
     * @param type $url
     */
    protected function redirect($url)
    {
        header('Location: ' . (string) $url);
        exit;
    }

    /**
     * 渲染页面
     *
     * @param type $name
     * @return type
     */
    protected function render($name, $data = array())
    {

        if (!empty($data) && is_array($data)) {
            $this->renderData = array_merge($this->renderData, $data);
        }
        $this->renderData['domain'] = $this->loadComponent('Domain');
        $render = new PhpRenderer(basePath() . DS . 'View');

        if (!empty($this->layout)) {
            $content = $render->fetch($name . '.html', $this->renderData);
            $params = array_merge($this->renderData, array('content' => $content));
            $render->render(self::$ci['response'], 'Layouts\\' . $this->layout . '.html', $params);
        } else {
            $render->render(self::$ci['response'], $name . '.html', $this->renderData);
        }
    }

    /**
     * 设置render数据
     *
     * @param type $method
     * @return type
     */
    protected function set($key, $value)
    {

        if (empty($key)) {
            return false;
        }

        $this->renderData[$key] = $value;
    }

    /**
     * 打印数据
     * @param type $data
     */
    protected function pr($data)
    {

        echo '<pre>';
        print_r($data);
    }

    /**
     * 用户登录验证
     */
    protected function checkLogin()
    {
        if (empty($this->params)) {
            $this->params = $_REQUEST;
        }
        $result = $this->api('User', 'checkLogin');
        if ($result['stat'] == 1) {
            $this->userId = $result['data']['user_id'];
            $this->uid = $result['data']['uid'];
            $this->role = $result['data']['role'];
            $this->signature = $result['data']['signature'];
            return true;
        }

        if (!empty($this->params['isApp']) && $this->params['isApp'] == 1) {
            $result = ['result' => ['status' => 9, 'data' => '用户未登录！']];
            echo json_encode($result);
            exit;
        } else {
            $req = self::$ci->get('request');
            if ($req->isXhr()) {
                $result['stat'] = 9;
                $result['data'] = '页面已过期，请刷新后重新登录';
                echo json_encode($result);
                exit;
            } else {
                $loginUrl = config('CommonData\GeneralUrl.loginUrl');
                $this->redirect($loginUrl);
            }
        }
    }

    /**
     * 获取url加密串
     */
    protected function getEncryptUrlKey($url, $data = array())
    {

        $result = $this->getCommon()->getEncryptUrlKey($url, $data, $this->signature);
        return $result;
    }

    /**
     * 校验url地址合法性
     */
    protected function checkUrlStatus($params = array())
    {

        $result = $this->getCommon()->checkUrlStatus($params, $this->params, $this->signature);
        return $result;
    }

    /**
     * 给老业务url加密
     */
    protected function enOldUrl($controller, $action, $urlStr)
    {

        return md5($controller . '-' . $action . '-' . $urlStr . '-' . $this->signature);
    }

    /**
     * 根据UA判断是否是PC端
     */
    protected function isWeb()
    {
        //当前UA
        $ua = $this->getCommon()->getUa();

        //PC端UA关键词
        $webUA = config('CommonData\WebUa.webUA');

        foreach ($webUA as $_webUA) {
            if (stristr($ua, $_webUA)) {
                return 1;
            }
        }
        return 3;
    }

    /**
     * 根据UA判断是否是微信浏览器
     */
    protected function isWx()
    {
        //当前UA
        $ua = $this->getCommon()->getUa();

        if (strpos($ua, 'MicroMessenger') === false) {
            return 0;
        }
        return 1;
    }

    /**
     * 判断当前版本号是否更新
     */
    protected function getAppVer($business = '', $params = [])
    {
        if (!empty($params['systemName'])) {
            if ($params['systemName'] == 'android') {
                $version = config("CommonData\Ver.app.{$business}.andriodVer");
            } else {
                $version = config("CommonData\Ver.app.{$business}.iosVer");
            }

            if (!empty($version) && $params['appVersionNumber'] >= $version) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断应用市场
     */
    protected function getAppChannel($appChannel = '')
    {

        $appNum = config('CommonData\AppChannel');
        $sourceId = !empty($appNum[$appChannel]) ? $appNum[$appChannel] : 0;

        return $sourceId;
    }

    /**
     * 判断设备来源
     */
    public function getClientType($systemName = '')
    {

        $sourceType = 8;

        if (!empty($systemName)) {
            $clientType = strtolower(substr($systemName, 0, 1));

            if ($clientType == 'i') {
                $sourceType = 7;
            }
            if ($clientType == 'h') {
                $sourceType = 2;
            }
        }

        return $sourceType;
    }

    /**
     * 获取业务层的版本号
     */
    public function getComponentVer($sourceType = 7)
    {
        $appVersion = !empty($this->params['appVersionNumber']) ? intval($this->params['appVersionNumber']) : 0;

        $componentVer = 0;
        $verConfig = config('CommonData\\Ver.appVer');

        if (empty($verConfig[$sourceType]) || empty($appVersion)) {
            return $componentVer;
        }

        $appVersion = str_pad($appVersion, 7, '0', STR_PAD_RIGHT);

        krsort($verConfig[$sourceType]);
        foreach ($verConfig[$sourceType] as $appVer => $comVer) {
            if ($appVersion >= $appVer) {
                $componentVer = $comVer;
                break;
            }
        }

        return $componentVer;
    }

    /**
     * 获取Header信息
     */
    protected function getHeaderInfos()
    {
        $headers = [];
        if (!empty($_SERVER)) {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * 是否是压测
     */
    protected function isPerformanceTest()
    {

        $headerInfos = $this->getHeaderInfos();

        if (isset($headerInfos['xes-request-type']) && $headerInfos['xes-request-type'] == 'performance-testing') {
            return 1;
        }

        return 0;
    }

}

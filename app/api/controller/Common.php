<?php

namespace app\api\controller;

use extend\lib\XibeiApiServer;
use app\common\service\Application as AppService;
use support\Request;
use think\facade\Cache;

/**
 * 公共控制器
 *
 * @package app\api\controller
 */
class Common
{
    /**
     * 当前请求的应用信息
     *
     * @var array
     */
    protected $appInfo = [];

    /**
     * 接口请求参数
     */
    protected $requestParams = [];

    /**
     * 接口请求业务数据
     *
     * @var array
     */
    protected $requestData = [];

    /**
     * 初始化
     *
     * @return string
     */
    public function beforeAction(Request $request)
    {
        if (false === stripos($request->header('Content-Type'), 'application/json')) {
            return $this->response(5031001, '请求类型错误');
        }
        if ($request->method() != 'POST') {
            return $this->response(5031001, '请求方法错误');
        }

        $this->requestParams = $request->post();

        $appId       = $this->requestParams['app_id'] ?? '';
        $timestamp   = $this->requestParams['timestamp'] ?? '';
        $sign        = $this->requestParams['sign'] ?? '';
        $signType    = $this->requestParams['sign_type'] ?? '';
        $encryptType = $this->requestParams['encrypt_type'] ?? '';
        $nonceStr    = $this->requestParams['nonce_str'] ?? '';

        if (empty($appId)) {
            return $this->response(5031001, 'app_id不能为空');
        }
        if (empty($timestamp)) {
            return $this->response(5031001, 'timestamp不能为空');
        }
        if (empty($encryptType) || $encryptType != 'AES') {
            return $this->response(5031001, 'encrypt_type无效');
        }
        if (empty($signType) || $signType != 'RSA') {
            return $this->response(5031001, 'sign_type无效');
        }
        if (empty($sign)) {
            return $this->response(5031001, 'sign不能为空');
        }
        if (empty($timestamp) || time() - $timestamp > 600) {
            return $this->response(5031103, '请求过期');
        }

        // 防重发
        if (!empty($nonceStr)) {
            $cachename = 'intercept:' . $appId . ':' . $nonceStr;
            if (Cache::has($cachename)) {
                return $this->response(5031104, '请求重复');
            }

            Cache::set($cachename, '1', 600);
        }

        // 获取应用信息
        $this->appInfo = AppService::info($appId);
        if (empty($this->appInfo)) {
            return $this->response(5031101, 'APP不存在');
        }

        $apiServer               = new XibeiApiServer();
        $apiServer->appId        = $this->appInfo['app_id'];
        $apiServer->appKey       = $this->appInfo['app_key'];
        $apiServer->rsaPublicKey = $this->appInfo['rsa_public_key'];

        // 验证签名
        if (!$apiServer->checkSign($this->requestParams, $signType)) {
//            return $this->response(5031102, '签名错误');
        }

        // 解密
        if (isset($this->requestParams['biz_content'])) {
            if ($encryptType == 'AES') {
                $bizContent = $apiServer->getDecryptContent($this->requestParams['biz_content']);
            } else {
                $bizContent = $this->requestParams['biz_content'];
            }

            $this->requestData = json_decode($bizContent, true);
        }
    }

    /**
     * 响应请求
     *
     * @param integer      $code 状态码 -1未登录 -2订单已变更 -3拉单失败 -4抢单
     * @param string       $message
     * @param array|string $data
     * @return string
     */
    protected function response($code = 0, $message = '', $data = [])
    {
        $resp = [
            'code' => $code,
            'msg'  => $message,
            'data' => $data,
        ];
        // 记录日志
        save_log('', 1, 'API接口访问日志', 'api', [
            'remote_ip'     => request()->getRealIp(true),
            'request_data'  => $this->requestData,
            'response_body' => $resp,
            'response_data' => $data,
        ]);

        return json($resp);
    }
}

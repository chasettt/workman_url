<?php

namespace extend\lib;

use \Exception;

/**
 * XibeiApiClient
 */
class XibeiApiClient
{
    /**
     * 应用id
     *
     * @var
     */
    public $appId;

    /**
     * 应用秘钥 用于数据加密
     *
     * @var
     */
    public $appKey;

    /**
     * 版本
     *
     * @var string
     */
    public $version = "1.0";

    /**
     * 接口请求超时时间
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * 签名类型
     * 支持md5和rsa
     *
     * @var string
     */
    public $signType = 'RSA';

    /**
     * 加密类型 为空表示不加密
     *
     * @var string
     */
    public $encryptType = "AES";

    /**
     * RSA私钥
     *
     * @var string
     */
    public $rsaPrivateKey = '';

    /**
     * 通信错误日志回调
     *
     * @var callback
     */
    public $logCallback;

    /**
     * 加密方法
     *
     * @param string $str
     * @return string
     */
    private function encrypt($str, $screct_key)
    {
        $cipher = "AES-256-CBC";

        //设置全0的IV
        $iv_size = openssl_cipher_iv_length($cipher);//16
        $iv      = str_repeat("\0", $iv_size);

        $encrypt_str = openssl_encrypt($str, $cipher, $screct_key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($encrypt_str);
    }

    /**
     * 解密方法
     *
     * @param string $str
     * @return string
     */
    private function decrypt($str, $screct_key)
    {
        $str    = base64_decode($str);
        $cipher = "AES-256-CBC";

        //设置全0的IV
        $iv_size = openssl_cipher_iv_length($cipher);//16
        $iv      = str_repeat("\0", $iv_size);

        $decrypt_str = openssl_decrypt($str, $cipher, $screct_key, OPENSSL_RAW_DATA);

        return $decrypt_str;
    }

    /**
     * 执行请求
     *
     * @param string $url       请求url
     * @param array  $apiParams 接口参数
     * @return bool|mixed
     */
    public function execute($url, array $apiParams = [])
    {
        // 组装系统参数
        $sysParams                 = [];
        $sysParams["app_id"]       = $this->appId;
        $sysParams["version"]      = $this->version;
        $sysParams["timestamp"]    = time();
        $sysParams['nonce_str']    = $this->generateNonceStr();
        $sysParams["encrypt_type"] = $this->encryptType;
        $sysParams["sign_type"]    = $this->signType;

        // 合并系统参数与接口参数为请求参数
        $requestParams = array_merge($sysParams, $apiParams);

        // 执行加密
        if ($this->encryptType == 'AES' && isset($apiParams['biz_content']) && !empty($apiParams['biz_content'])) {
            $requestParams['biz_content'] = $this->encrypt($apiParams['biz_content'], $this->appKey);
        }

        // 执行签名
        $requestParams["sign"] = $this->generateSign($requestParams, $this->signType);

        // 发起HTTP请求
        try {
            $resp = $this->post($url, $requestParams);
        } catch (\Exception $e) {
            $this->log($url, $apiParams, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());

            return false;
        }

        // 解析返回结果
        $respObject = json_decode($resp, true);

        // 返回的HTTP文本不是标准JSON，记下错误日志
        if (null === $respObject) {
            $this->log($url, $apiParams, "HTTP_RESPONSE_NOT_WELL_FORMED", $resp);

            return false;
        }

        // 解密
        if (
            $this->encryptType == 'AES'
            && isset($respObject['data'])
            && !empty($respObject['data'])
            && is_string($respObject['data'])
        ) {
            $bizContent = $this->decrypt($respObject['data'], $this->appKey);

            $respObject['data'] = json_decode($bizContent, true);
        }

        return $respObject;
    }

    /**
     * 生成签名
     *
     * @param array  $params
     * @param string $signType
     * @return string
     */
    private function generateSign($params, $signType = "RSA")
    {
        $signContent = $this->getSignContent($params);
        switch ($signType) {
            case 'MD5':
                $sign = $this->md5Sign($signContent, $this->appKey);
                break;
            case 'RSA':
                $sign = $this->rsaSign($signContent, $this->rsaPrivateKey);
                break;
            default:
                $sign = '';
        }

        return $sign;
    }

    /**
     * 生成MD5签名
     *
     * @param $content
     * @param $key
     * @return string
     */
    private function md5Sign($content, $key)
    {
        $sign = md5($content . '&key=' . $key);

        return $sign;
    }

    /**
     * 生成RSA签名
     *
     * @param $data
     * @param $rsaPrivateKey
     * @return string
     */
    private function rsaSign($data, $rsaPrivateKey)
    {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($rsaPrivateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);

        $sign = base64_encode($sign);

        return $sign;
    }

    /**
     * 生成待签名字符串
     *
     * @param array $params
     * @return string
     */
    private function getSignContent($params = [])
    {
        $paramString = "";
        if (!empty($params)) {
            ksort($params);
            foreach ($params as $key => $value) {
                if ($value !== '' && !is_array($value) && "@" != substr($value, 0, 1)) {
                    $paramString .= $key . "=" . $value . '&';
                }
            }
        }

        return rtrim($paramString, '&');
    }

    /**
     * 生成随机字串
     *
     * @param number $length 长度，默认为16，最长为32字节
     * @return string
     */
    private function generateNonceStr($length = 16)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_";
        $str   = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $str;
    }

    /**
     * 发送请求
     *
     * @param string $url
     * @param array  $postFields
     * @return bool|string
     * @throws Exception
     */
    public function post($url, $postFields)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);//30秒超时

        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // 判断是不是文件上传
        $postMultipart = false;
        if (is_array($postFields) && count($postFields) > 0) {
            foreach ($postFields as $k => $v) {
                if ("@" == substr($v, 0, 1)) {
                    $postMultipart  = true;
                    $postFields[$k] = new \CURLFile(substr($v, 1));
                }
            }
            unset ($k, $v);
        }

        // 文件上传用multipart/form-data，否则用application/json
        if ($postMultipart) {
            $headers = ['content-type: multipart/form-data'];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        } else {
            $headers = ['content-type: application/json'];
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($response, $httpStatusCode);
            }
        }

        curl_close($ch);

        return $response;
    }

    /**
     * http错误日志记录
     *
     * @param        $requestUrl
     * @param        $requestData
     * @param        $errorCode
     * @param        $responseTxt
     */
    protected function log($requestUrl, $requestData, $errorCode, $responseTxt)
    {
        if (is_callable($this->logCallback)) {
            return call_user_func($this->logCallback, $requestUrl, $requestData, $errorCode, $responseTxt);
        }
    }
}

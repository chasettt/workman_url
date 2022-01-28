<?php

namespace extend\lib;

/**
 * XibeiApiServer
 */
class XibeiApiServer
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
     * RSA公钥
     *
     * @var string
     */
    public $rsaPublicKey = '';

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
     * 验证签名
     *
     * @param $signData
     * @param $signType
     * @return bool
     */
    public function checkSign($signData, $signType)
    {
        $sign = $signData['sign'];
        unset($signData['sign']);
        $signContent = $this->getSignContent($signData);

        switch ($signType) {
            case 'MD5':
                $result = $this->md5Verify($signContent, $sign, $this->appKey);
                break;
            case 'RSA':
                $result = $this->rsaVerify($signContent, $sign, $this->rsaPublicKey);
                break;
            default:
                $result = false;
        }

        return $result;
    }

    /**
     * 获取加密内容
     *
     * @param $content
     * @return string
     */
    public function getDecryptContent($content)
    {
        return $this->decrypt($content, $this->appKey);
    }

    /**
     * MD5签名验签
     *
     * @param $data
     * @param $sign
     * @param $key
     * @return bool
     */
    private function md5Verify($data, $sign, $key)
    {
        return $sign === $this->md5Sign($data, $key);
    }

    /**
     * RSA签名验签
     *
     * @param $data
     * @param $sign
     * @param $rsaPublicKey
     * @return bool
     */
    private function rsaVerify($data, $sign, $rsaPublicKey)
    {
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($rsaPublicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        //调用openssl内置方法验签，返回bool值
        $result = (openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256) === 1);

        return $result;
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
}
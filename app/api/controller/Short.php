<?php

namespace app\api\controller;

use app\common\service\ShortUrl;

/**
 * 生成短网址接口
 *
 * @package app\api\controller
 */
class Short extends Common
{
    /**
     * 创建短网址
     *
     * curl -X POST 'https://xbdwz.com/api/short/url'
     * -H 'Content-Type:application/json; charset=UTF-8'
     * -d
     * '{"long_url":"http://vip.xibei.com.cn","expire_time":0}'
     *
     * 单次请求不超过 200 条
     *
     * 短网址有效期，目前支持："long-term"：长期   "1-year"：1年
     */
    public function url()
    {
        $appId      = $this->appInfo['app_id'];
        $longUrl    = $this->requestData['long_url'];
        $expireTime = $this->requestData['expire_time'] ?? 0;

        if (empty($longUrl)) {
            return $this->response(5003, '请求数据不能为空');
        }

        try {
            $shortPath = ShortUrl::create(
                $appId,
                $longUrl,
                $expireTime
            );

        } catch (\Exception $e) {
            return $this->response(5004, '生成失败');
        }

        $shortUrl = parse_url(rtrim(config('app.app_host'), '/'))['host'] . '/' . $shortPath;

        $data = [
            'long_url'    => $longUrl,
            'short_url'   => $shortUrl,
            'short_path'  => $shortPath,
            'expire_time' => $expireTime,
        ];

        return $this->response(0, 'success', $data);
    }

    /**
     * 批量创建短网址
     *
     * curl -X POST 'https://xbdwz.cn/api/short/urls'
     * -H 'Content-Type:application/json; charset=UTF-8'
     * -d
     * '[{"long_url":"http://vip.xibei.com.cn","expire_time":0},{"long_url":"http://shop.xibei.com.cn","expire_time":1620211318}]'
     *
     * 单次请求不超过 200 条
     *
     * 短网址有效期，目前支持："long-term"：长期   "1-year"：1年
     */
    public function urls()
    {
        $urls  = $this->requestData;
        $appId = $this->appInfo['app_id'];
        if (empty($urls)) {
            return $this->response(5003, '请求数据不能为空');
        }

        if (count($urls) > 200) {
            return $this->response(5004, '每次最多创建200条');
        }

        $list = [];
        foreach ($urls as $url) {
            $longUrl    = $url['long_url'];
            $expireTime = $url['expire_time'];

            $shortPath = ShortUrl::create(
                $appId,
                $longUrl,
                $expireTime
            );

            $shortUrl = parse_url(rtrim(config('app.app_host'), '/'))['host'] . '/' . $shortPath;

            $list[] = [
                'long_url'    => $longUrl,
                'short_url'   => $shortUrl,
                'expire_time' => $expireTime,
            ];
        }

        return $this->response(0, 'success', $list);
    }
}

<?php

namespace app\common\service;

use app\common\model\ShortUrl as ShortUrlModel;
use think\facade\Cache;

/**
 * 短网址服务
 *
 * @package app\common\service
 */
class ShortUrl
{
    /**
     * 生成短网址
     *
     * @param string $appId
     * @param string $longUrl
     * @param int    $expireTime
     * @return string 短网址路径
     */
    public static function create($appId, $longUrl, $expireTime = 0)
    {
        $i = rand(0, 999);
        do {
            if ($i == 999) {
                $i = 0;
            }
            $i++;

            //            $shortPath = self::generateShortPath2($longUrl);
            $shortPath = self::generateShortPath();

            $res = ShortUrlModel::where('short_path', $shortPath)->find();
            if (empty($res)) {
                $info = ShortUrlModel::create([
                    'app_id'      => $appId,
                    'long_url'    => $longUrl,
                    'short_path'  => $shortPath,
                    'expire_time' => $expireTime,
                ]);

                $cacheName = 'url:' . $shortPath;
                Cache::set($cacheName, $info->toArray(), $expireTime ? : 86400);

                return $shortPath;
            }
        } while ($res);
    }

    /**
     * 短网址路径查询网址
     *
     * @param string $shortPath
     * @return array|null
     */
    public static function query($shortPath)
    {
        $cacheName = 'url:' . $shortPath;

        if (Cache::has($cacheName)) {
            $info = Cache::get($cacheName);
        } else {
            $info = ShortUrlModel::where('short_path', $shortPath)->find();
            if (!empty($info)) {
                Cache::set($cacheName, $info, 86400);
            }
        }

        return $info;
    }

    /**
     * 增加短网址访问量
     *
     * @param string $shortPath
     * @return void
     */
    public static function pvInc($shortPath)
    {
        try {
            ShortUrlModel::where('short_path', $shortPath)->inc('pv')->update();
        } catch (\Exception $e) {

        }
    }

    /**
     * 生成短网址路径
     *
     * @param int $length
     * @return string
     */
    public static function generateShortPath($length = 6)
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
     * 根据url生成唯一短网址路径
     *
     * @param string $url
     * @return string
     */
    public static function generateShortPath2($url)
    {
        // 密码字符集
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        // 对传入网址进行 MD5 加密
        $hex = md5(config('app_key') . $url);

        $resUrl = [];
        for ($i = 0; $i < 4; $i++) {
            // 把加密字符按照8位一组16进制与0x3FFFFFFF进行位与运算
            $subHex   = substr($hex, $i * 8, 8);
            $lHexLong = 0x3FFFFFFF & (1 * ('0x' . $subHex));

            $outChars = "";
            for ($j = 0; $j < 6; $j++) {
                // 把得到的值与 0x0000003D 进行位与运算，取得字符数组 chars 索引
                $index = 0x0000003D & $lHexLong;
                // 把取得的字符相加
                $outChars .= $chars[$index];
                // 每次循环按位右移 5 位
                $lHexLong = $lHexLong >> 5;
            }

            // 把字符串存入对应索引的输出数组
            $resUrl[$i] = $outChars;
        }

        return $resUrl[0];
    }
}

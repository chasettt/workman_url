<?php

namespace app\common\service;

use extend\api\Mdc as MdcApi;
use think\facade\Cache;

/**
 * 应用服务
 *
 * @package app\common\service
 */
class Application
{
    /**
     * 缓存前缀
     * @var string
     */
    public static $cachePrefix = 'application:';

    /**
     * 同步应用列表
     *
     * @throws \Exception
     */
    public static function sync()
    {
        $mdcApi = new MdcApi();

        // 获取主数据应用列表
        $result = $mdcApi->getAppList();
        if (false === $result) {
            save_log([
                'err_msg' => $mdcApi->errMsg,
            ], 2, '同步主数据应用列表失败');

            throw new \Exception('同步主数据应用列表失败');
        }

        foreach ($result['list'] as $masterAppInfo) {

            $appId     = $masterAppInfo['token'];
            $cacheName = self::$cachePrefix . $appId;

            // 添加/更新应用
            Cache::set($cacheName, [
                'app_id'         => $masterAppInfo['token'],
                'app_name'       => $masterAppInfo['dept'],
                'app_key'        => $masterAppInfo['key'],
                'rsa_public_key' => $masterAppInfo['rsa_public_key'],
            ]);
        }
    }

    /**
     * 获取应用信息
     *
     * @param string $appid
     * @return mixed|null
     */
    public static function info($appid)
    {
        $cachename = self::$cachePrefix . $appid;
        $appInfo   = Cache::get($cachename, null);

        return $appInfo;
    }
}

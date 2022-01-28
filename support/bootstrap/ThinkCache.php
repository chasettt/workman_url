<?php

namespace support\bootstrap;

use Webman\Bootstrap;
use think\facade\Cache;

class ThinkCache implements Bootstrap
{
    // 进程启动时调用
    public static function start($worker)
    {
        // 配置
        Cache::config(config('thinkcache'));
    }
}

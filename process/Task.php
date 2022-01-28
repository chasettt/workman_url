<?php

namespace process;

use app\common\service\Application as AppService;
use Workerman\Crontab\Crontab;

/**
 * 定时任务
 */
class Task
{
    public function onWorkerStart()
    {
        new Crontab('0 * * * *', function(){
            AppService::sync();
            echo date('Y-m-d H:i:s')."\n";
        });

        // 每天的7点50执行，注意这里省略了秒位.
        new Crontab('50 7 * * *', function(){
            echo date('Y-m-d H:i:s')."\n";
        });
    }
}
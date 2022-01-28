<?php

use support\Env;

return [
    'default'    =>    'mysql',
    'connections'    =>    [
        'mysql'    =>    [
            // 数据库类型
            'type'        => 'mysql',
            // 服务器地址
            'hostname'    => Env::get('database.hostname'),
            // 数据库名
            'database'    => 'xibei_dwz',
            // 数据库用户名
            'username'    => Env::get('database.username'),
            // 数据库密码
            'password'    => Env::get('database.password'),
            // 数据库连接端口
            'hostport'    => '3306',
            // 数据库连接参数
            'params'      => [],
            // 数据库编码默认采用utf8
            'charset'     => 'utf8mb4',
            // 数据库表前缀
            'prefix'      => 'xb_',
            // 断线重连
            'break_reconnect' => true,
            // 关闭SQL监听日志
            'trigger_sql' => false,
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 数据集返回类型
            'resultset_type'  => 'array',
        ],
    ],
];
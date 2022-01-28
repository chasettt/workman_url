<?php

use support\Env;

return [
    'default' => 'redis',
    'stores'  => [
        'file'  => [
            'type'   => 'File',
            // 缓存保存目录
            'path'   => runtime_path() . '/cache/',
            // 缓存前缀
            'prefix' => '',
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
        ],
        'redis' => [
            'type'      => 'redis',
            'host'      => Env::get('redis.hostname'),
            'port'      => Env::get('redis.port'),
            'select'    => Env::get('redis.db'),
            'password'  => Env::get('redis.password'),
            'prefix'    => '',
            'expire'    => 0,
            'serialize' => [
                function ($data) {
                    if (is_scalar($data)) {
                        return $data;
                    }

                    return 'think_serialize:' . serialize($data);
                }, function ($data) {
                    if (0 === strpos($data, 'think_serialize:')) {
                        return unserialize(substr($data, 16));
                    } else {
                        return $data;
                    }
                },
            ],
        ],
    ],
];

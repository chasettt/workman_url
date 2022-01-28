<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Env;

return [
    'debug'               => Env::get('app_debug'),
    'default_timezone'    => 'Asia/Shanghai',
    // 应用ID（西贝开发者）
    'app_id'              => 'app5ff9be1f93407',
    // 应用加密秘钥(西贝开发者)
    'app_key'             => Env::get('app_key'),
    // 应用公钥
    'app_rsa_public_key'  => Env::get('app_rsa_public_key'),
    // 应用私钥
    'app_rsa_private_key' => Env::get('app_rsa_private_key'),
    // 应用名称
    'app_name'            => '西贝短网址服务',
    // 应用地址
    'app_host'            => Env::get('app_host'),
];

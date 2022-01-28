<?php
/**
 * Here is your custom functions.
 */

use support\Env;

/**
 * 西贝标准日志方法
 *
 * @param array|string $info     日志信息，可以是字符串或者数组
 * @param int          $level    日志级别 1-info，2-error，3-warning，4-notice，5-debug，6-sql，7-middleware
 * @param string       $title    日志标题
 * @param string       $path     日志存放目录
 * @param array        $extParam 扩展字段，添加后可显示在日志系统第一层，例如接口error_code等
 */
function save_log($info = '', $level = 1, $title = '', $path = 'common', $extParam = [])
{
    // 日志路径
    $logPath = runtime_path() . '/log' . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR;
    if (!is_dir($logPath)) {
        mkdir($logPath, 0755, true);
    }

    $logName = date('d') . '.log';

    $typeString = '';
    if ($level == 1) {
        $typeString .= 'info';
    } elseif ($level == 2) {
        $typeString .= 'error';
    } elseif ($level == 3) {
        $typeString .= 'warning';
    } elseif ($level == 4) {
        $typeString .= 'notice';
    } elseif ($level == 5) {
        $typeString .= 'debug';     // 调试信息
    } elseif ($level == 6) {
        $typeString .= 'sql';       // 数据库异常
    } elseif ($level == 7) {
        $typeString .= 'middleware';// 第三方中间件异常
    } else {
        $typeString .= $level;
    }

    // 日志信息
    $message = [
        'title'          => $title,
        'level'          => $typeString,
        'request_time'   => date('Y-m-d H:i:s'),
        'request_method' => 'CLI',
        'request_uri'    => request()->uri(),
        'remote_ip'      => request()->getRealIp($safe_mode=true),
        'request_ttl'    => 0,
        'msg'            => $info,
    ];

    // php提交信息
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $message['input'] = urldecode($input);
    }

    // 扩展字段，根据项目，有的项目需要对某些参数进行统计，必须放在一维数组里。普通项目无需设置此字段
    // 对于错误码多的项目，可在此字段附加错误码，方便统计
    if (!empty($extParam) && is_array($extParam)) {
        $message = array_merge($message, $extParam);
    }

    error_log(json_encode($message, JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL, 3, $logPath . $logName);
}

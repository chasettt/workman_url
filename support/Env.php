<?php

namespace support;

class Env
{
    /**
     * 环境变量数据
     * @var array
     */
    protected static $data = [];

    /**
     * 读取环境变量定义文件
     * @access public
     * @param  string    $file  环境变量定义文件
     * @return void
     */
    public static function load($file)
    {
        self::set($_ENV);
        if(!is_file($file)){
            return;
        }
        $env = parse_ini_file($file, true);
        self::set($env);
    }

    /**
     * 获取环境变量值
     * @access public
     * @param  string    $name 环境变量名
     * @param  mixed     $default  默认值
     */
    public static function get($name=null, $default = null)
    {
        if($name==null){
            return self::$data;
        }
        $name=strtoupper($name);
        $name = strtoupper(str_replace('.', '_', $name));
        if (isset(self::$data[$name])) {
            return  self::$data[$name];
        }
        return $default;
    }

    /**
     * 设置环境变量值
     * @access public
     * @param  string|array  $env   环境变量
     * @param  mixed         $value  值
     * @return void
     */
    public static function set($env, $value = null)
    {

        if (is_array($env)) {
            $env = array_change_key_case($env, CASE_UPPER);
            foreach ($env as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        self::$data[$key . '_' . strtoupper($k)] = trim($v);
                    }
                } else {
                    self::$data[$key] = trim($val);
                }
            }
        } else {
            $name = strtoupper(str_replace('.', '_', $env));
            self::$data[$name] = trim($value);
        }
    }
}

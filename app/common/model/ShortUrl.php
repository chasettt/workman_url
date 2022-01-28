<?php

namespace app\common\model;

/**
 * 短网址服务
 *
 * @package app\common\model
 */
class ShortUrl extends Common
{
    /**
     * 数据表名称
     * @var string
     */
    public $name = 'short_urls';

    /**
     * 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
     * @var bool|string
     */
    protected $autoWriteTimestamp = true;
}

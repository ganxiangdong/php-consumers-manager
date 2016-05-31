<?php
namespace Xd\QueueConsumersManager;

/**
 * 辅助类
 * Class Helper
 * @package Xd\QueueConsumersManager
 */
class Helper
{
    /**
     * 格式化显示的内存大小
     * @param int $size byte 字节
     * @return string
     */
    public static function formatShowMemorySize($size)
    {
        if ($size <= 0) {
            $showSize = '0 B';
        } else if ($size < 1024) {
            $showSize = "{$size} B";
        } else if ($size < 1048576) {
            $showSize = round($size/1024, 1).' KB';
        } else {
            $showSize = round($size/1048576, 1).' MB';
        }
        return $showSize;
    }

    /**
     * 格式化显示的时间
     * @param int $second 秒数
     * @return string
     */
    public static function formatShowTime($second)
    {
        if ($second <= 0) {
            $showTime = '刚刚';
        } else if ($second < 60) {
            $showTime = "{$second}秒";
        } else if ($second < 3600) {
            $showTime = floor($second/60)."分钟";
        } else if ($second < 86400) {
            $showTime = floor($second/3600)."小时";
        } else {
            $showTime = round($second/86400,1)."天";
        }
        return $showTime;
    }

}
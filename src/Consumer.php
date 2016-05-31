<?php
namespace Xd\QueueConsumersManager;

/**
 * 消费者
 * Class Consumer
 * @package Xd\QueueConsumersManager
 */
abstract class Consumer
{
    /**
     * 运行消费者
     */
    abstract public function run();

    /**
     * 关闭消费者
     */
    abstract public function shutdown();
}
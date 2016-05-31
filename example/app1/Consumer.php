<?php
/**
 * 示例1
 */
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// 引入composer自动加载
$basePath = substr(__DIR__, 0, -13);
$loader = require __DIR__.'/../../vendor_/autoload.php';
$loader->setPsr4('Xd\\QueueConsumersManager\\', $basePath.'/src');

class myConsumer extends \Xd\QueueConsumersManager\Consumer
{
    //开始运行,当队列开始后会调用此方法
    public function run()
    {
        //模拟获取,消费队列消息
        while(true) {
            sleep(2);//模拟耗时处理
            \Xd\QueueConsumersManager\Manager::noticeFetchedQueueMsg();
        }
    }

    /**
     * 关闭consumer
     */
    public function shutdown()
    {
        //关闭连接等处理
    }
}

$myConsumer = new myConsumer();

$consumerManager = \Xd\QueueConsumersManager\Manager::getInstance($myConsumer);
$consumerManager->receivedMax = 1000;//设置每个consumer最多请求多少次消息后重启,默认为1000
$consumerManager->run();
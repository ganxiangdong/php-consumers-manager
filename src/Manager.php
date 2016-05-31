<?php
namespace Xd\QueueConsumersManager;

/**
 * 消费者管理类
 * Class Manager
 */
class Manager
{
    /**
     * 单例模式的实例化对象
     * @var Manager
     */
    private static $instance = null;

    /**
     * Consumer实例化对象
     * @var Consumer
     */
    private $consumerInstance = null;

    /**
     * 开始运行时间
     * @var string
     */
    private $startRunTime = '';

    /**
     * 当前请求接收消费数据次数
     * @var int
     */
    public $receivedNum = 0;

    /**
     * @var int 最多请求接收消费数据多少条后重启
     */
    public $receivedMax = 1000;

    /**
     * 是否在等待平滑关闭
     * @var bool
     */
    public $isWaitingShutdown = false;

    /**
     * 是否等待显示进程状态
     * @var bool
     */
    public $isWaitingShowStatus = false;

    /**
     * 上次查看进程状态时间
     * @var int
     */
    public $lastShowStatusTime = 0;

    /**
     * 启动应用路径参数
     * @var string
     */
    private $startAppPath = '';

    /**
     * 数据目录路径
     * @var string
     */
    private $dataDir = '';

    /**
     * 当前运行的pid,主要用于日志的显示
     * @var string
     */
    private $pid = '';

    /**
     * ConsumerManager constructor
     * @param Consumer $consumer
     */
    private function __construct(Consumer $consumer)
    {
        //初使化
        $this->startRunTime = date('Y-m-d H:i:s');
        $this->consumerInstance = $consumer;
        $this->dataDir = substr(__DIR__, 0, -4).'/data';
        $this->lastShowStatusTime = time();
        if (function_exists('posix_getpid')) {
            $this->pid = posix_getpid();
        }
        $this->init();
    }

    /**
     * 单例禁止外部clone
     */
    private function __clone()
    {
    }

    /**
     * 获取实例化对象
     * @param Consumer|null $consumer
     * @return Manager
     * @throws \Exception
     */
    public static function getInstance(Consumer $consumer = null)
    {
        if (self::$instance === null) {
            if ($consumer === null) {
                throw new \Exception("ConsumerManager实例化时不能传入null");
            }
            self::$instance = new self($consumer);
        }
        return self::$instance;
    }

    /**
     * 初使化
     */
    private function init()
    {
        //检查运行是否正确
        try {
            //检查环境
            $this->checkRunEnv();
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }

        //初使化cli运行参数
        $this->initArgv();
    }

    /**
     * 检查运行环境
     */
    private function checkRunEnv()
    {
        if (php_sapi_name() != 'cli') {
            throw new \Exception("请使用cli模式运行");
        }
    }

    /**
     * 初使化参数
     */
    private function initArgv()
    {
        //保存启动参数
        $argv = $GLOBALS['argv'];
        $this->startAppPath = $argv[0];
    }

    /**
     * 开始运行
     */
    public function run()
    {
        //开始运行注入进来的consumer对象的run方法
        $this->consumerInstance->run();

    }

    /**
     * 关闭
     */
    public function shutdown()
    {
        //调用consumer的shutdown方法,关闭连接
        $this->consumerInstance->shutdown();
        exit;
    }

    /**
     * 重启
     */
    public function restart()
    {
        if (function_exists('exec')) {
            //新创建一个消费者
            $appPath = $this->startAppPath;
            $runLogFilePath = $this->getRunLogFilePath();
            $command = "php {$appPath} >> {$runLogFilePath} &";
            exec($command);
            //退出当前脚本
            $this->shutdown();
        } else {
            $this->writeLog("未能自动重启,因为exec方法被禁用");
        }
    }

    /**
     * 通知完成一次获取队列消息
     * @throws \Exception
     */
    public static function noticeFetchedQueueMsg()
    {
        //没有实例化则不充许调用
        if (self::$instance === null) {
            throw new \Exception('请先调用ConsumerManger类的getInstance进行实例化');
        }


        //获取consumerManager实例化对象
        $consumerManager = self::getInstance();

        //处理外部命令
        $consumerManager->handleOutCommand();

        //请求完成次数++
        $consumerManager->receivedNum ++;

        if ($consumerManager->isWaitingShutdown) {
            //收到关闭命令
            $consumerManager->shutdown();
        } else if ($consumerManager->isWaitingShowStatus) {
            //收到查看状态命令
            $consumerManager->showStatus();
        } else if ($consumerManager->receivedNum >= $consumerManager->receivedMax) {
            //超过定义请求完成次数重启
            $consumerManager->restart();
        }
    }

    /**
     * 处理外部命令
     */
    private function handleOutCommand()
    {
        //通过判断是否有某些操作的文件存在来作为控制信号

        //获取相关文件名
        $baseFileName = str_replace('/', '--', $this->startAppPath);
        $baseFilePath = $this->dataDir.'/'.$baseFileName;
        $shutdownFilePath = $baseFilePath.'.shutdown';
        $statusFilePath = $baseFilePath.'.status';

        //清空Php对文件状态的缓存
        clearstatcache($shutdownFilePath);
        clearstatcache($statusFilePath);

        //处理文件状态对应的操作
        if (is_file($shutdownFilePath)) { //是否有关闭命令
            $this->isWaitingShutdown = true;
        } else if (is_file($statusFilePath)) { //是否有查看状态命令
            //通过标记状态文件的时间来控制是否展示过了此次状态
            $statusFileTime = filectime($statusFilePath);
            if ($this->lastShowStatusTime < $statusFileTime) {
                $this->isWaitingShowStatus = true;
                $this->lastShowStatusTime = $statusFileTime;
            }
        }
    }

    /**
     * 显示进程的状态
     */
    private function showStatus()
    {
        $receivedNum = $this->receivedNum;
        $startRunTime = $this->startRunTime;
        $runTime = Helper::formatShowTime(time() - strtotime($this->startRunTime));
        $usageMemory = Helper::formatShowMemorySize(memory_get_usage());
        $usagePeakMemory = Helper::formatShowMemorySize(memory_get_peak_usage());
        $statusInfoStr = ' | '.$startRunTime
                        .' | '.str_pad($runTime, 9, ' ')
                        .' | '.str_pad(''.$receivedNum.'次', 16,' ')
                        .' | '.str_pad($usageMemory, 8, ' ')
                        .' | '.str_pad($usagePeakMemory, 8, ' ').' |'
                        .PHP_EOL;
        $statusInfoStr = ' '.str_pad('', 74, '-').PHP_EOL.$statusInfoStr.PHP_EOL;
        $this->writeToStatusFile($statusInfoStr);
        $this->isWaitingShowStatus = false;
    }

    /**
     * 向状态文件写入内容
     * @param string $content
     */
    private function writeToStatusFile($content)
    {
        $fileName = str_replace('/', '--', $this->startAppPath);
        $filePath = $this->dataDir.'/'.$fileName.'.status.data';
        $isOk = file_put_contents($filePath, $content, FILE_APPEND);
        if (!$isOk) {
            //写入文件失败
            $this->writeLog("写入文件{$filePath}失败");
        }
    }

    /**
     * 写日志
     * @param string $content 日志内容
     */
    public function writeLog($content)
    {
        $nowTime = date('Y-m-d H:i:s');
        $content = PHP_EOL."【{$nowTime}】【{$this->pid}】【{$this->startAppPath}】:{$content}";
        $logFilePath = $this->getRunLogFilePath();
        $isOk = file_put_contents($logFilePath, $content, FILE_APPEND);
        if (!$isOk) {
            //写入文件失败
            echo "写入文件{$logFilePath}失败";
        }
    }

    /**
     * 获取运行日志文件路径
     */
    private function getRunLogFilePath()
    {
        $appPathArr = explode('/', $this->startAppPath);
        $runLogFileName = end($appPathArr);
        $runLogFilePath = "/var/log/{$runLogFileName}.log";
        return $runLogFilePath;
    }
}
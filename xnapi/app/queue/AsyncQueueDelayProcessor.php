<?php
namespace app\queue;

use os\AsyncQueue;
use Workerman\Timer;

class AsyncQueueDelayProcessor
{
   
    protected $scheduler = [
        'OrderBuild'      => 100,
        'OrderNotify'     => 300,
        'AsyncOrderQuery' => 300,
        'DelayDispatcher' => 300,
    ];

    public function onWorkerStart()
    {
        // 每秒处理一次延迟任务转移
        Timer::add(1, function () {
            $this->processDelayTasks();
        });
        Timer::add(2, function () {
            $this->trimStreams();
        });
    }

    /**
     * 处理所有任务的延迟转移
     */
    public function processDelayTasks()
    {
        
        foreach ($this->scheduler as $task => $expire) {
            AsyncQueue::handleDelayByTask($task);
        }
    }
    
    public function trimStreams()
    {
        foreach ($this->scheduler as $task => $expire) {
            if($expire > 0){
                AsyncQueue::trimExpiredMessages([$task], $expire);
            }
        }
        
    }
   
} 
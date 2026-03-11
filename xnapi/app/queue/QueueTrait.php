<?php
namespace app\queue;

use os\AsyncQueue;
use Workerman\Timer;

trait QueueTrait
{
    //protected $expire = 600;      // 消息保留秒数

    public function onWorkerStart()
    {
        $pid = getmypid();
        print_r($this->task . "进程启动,进程ID:{$pid}\n");
        //处理消费者
        AsyncQueue::autoConsume($this->mode, $this->task, function($data){
            $this->ok($data);
        });
        
        
       
    }

   
}
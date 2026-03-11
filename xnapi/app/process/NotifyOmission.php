<?php
namespace app\process;

use Workerman\Timer;
use app\api\traits\Pro;

class NotifyOmission
{
    
    public function onWorkerStart()
    {
        
        // 每秒钟执行一次
        Timer::add(10, function () {
            $this->exTask();
        });

    }
    
    public function exTask()
    {
        
        $list = Pro::model('Order')->where(['status' => 10,'notify_number' => 0])->whereTime('createtime','-2 hours')->limit(30)->column('orderid');
        
        foreach ($list as $orderid){
            print_r("回调监控\n");
            \os\AsyncQueue::pushTask('OrderNotify', $orderid,1);
            
        }
        
    }
    
}
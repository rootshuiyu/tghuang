<?php
namespace app\process;

use Workerman\Timer;
use app\api\traits\Pro;

class OrderMatch
{
    
    public function onWorkerStart()
    {
        
        /*Timer::add(5, function () {
            $this->exTask();
            print_r("匹配转移---\n");
        });*/

    }
    
    public function exTask()
    {
        return;
        $time = time() - 10;
        $list  = Pro::model('Order')->where('status',0)->where('match_time','<',$time)->column('orderid');
        
        foreach ($list as $orderid){
            print_r("订单号：$orderid\n");
            Pro::logger('order')->resid($orderid)->info('匹配转移');
            
            \os\AsyncQueue::pushTask('OrderBuild', $orderid,1);
            
        }
        
    }
}
<?php
namespace app\queue;

use os\AsyncQueue;
use app\api\traits\Pro;

class AsyncOrderQuery
{
    use \app\queue\QueueTrait;
    //\os\AsyncQueue::pushTask('usera', ['foo' => 'bar'],3);
    protected $mode = 'compete'; // 或 'compete'/broadcast
    protected $task = 'AsyncOrderQuery';      // 业务/任务名
    
    // 重试配置
    protected $maxRetries = 3;     // 最大重试次数
    protected $retryInterval = 5;  // 重试间隔（秒）
    
    public function ok($data)
    {
        $orderid = $data['data'];
        $order = Pro::model('Order')->where(['orderid' => $orderid , 'status' => 2])->find();
        if(!$order){
            return;
        }
        
        $moudel = Pro::module($order['access']['module']);
        $order->query_time = time() + $moudel->query_delay + 10;
        $order->save();
        
        $lock = Pro::exlock('query_order_' . $orderid , $moudel->query_delay );
        if(!$lock){
            return;
        }
        
        $payResult = $moudel->queryOrder($order);
        if(!$payResult){
            Pro::console("队列查询:未支付".$orderid);
            \os\AsyncQueue::pushTask('AsyncOrderQuery', $orderid,$moudel->query_delay);
        }
        
    }
}
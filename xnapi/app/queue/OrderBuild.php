<?php
namespace app\queue;

use os\AsyncQueue;
use app\api\traits\Pro;

class OrderBuild
{
    use \app\queue\QueueTrait;
    //\os\AsyncQueue::pushTask('usera', ['foo' => 'bar'],3);
    protected $mode = 'compete'; // 或 'compete'/broadcast
    protected $task = 'OrderBuild';      // 业务/任务名
    
    // 重试配置
    protected $maxRetries = 3;     // 最大重试次数
    protected $retryInterval = 10;  // 重试间隔（秒）
    
    public function ok($data)
    {
        
        $orderid = $data['data'];
        $orderModel = Pro::model('Order');
        $order = $orderModel->where(['orderid' => $orderid , 'status' => 0])->find();;
        if(!$order){
            Pro::console('订单不存在'.$orderid);
            return;
        }
        
        if(!$order['ip']){
            Pro::console('未读取到用户IP');
            //\os\AsyncQueue::pushTask('OrderBuild', $order['orderid'],1);
            //return;
            
        }
        
        /*if($order['stay_time'] == 0){
            $order->run_info = '等待用户链接';
            $order->save();
            
            \os\AsyncQueue::pushTask('OrderBuild', $order['orderid']);
            return;
        }*/
        
        /*$prohibit_str = sconfig('allow_build_city');
        if($prohibit_str){
            $haystack = $order['city'];
            $needle = explode("\r\n",$prohibit_str);
            
            $prohibit = false;
            foreach ($needle as $work) {
                if (strpos($haystack , $work) !== false){
                    $prohibit = true;
                    break;
                }
            }
            if(!$prohibit){
                Pro::model('Order')->setOrderData($order['orderid'],['run_info' => '地区限制']);
                return;
            }
        }*/
        
        $lock = Pro::exlock('build_order_' . $orderid , 20 );
        if(!$lock){
            \os\AsyncQueue::pushTask('OrderBuild', $orderid,3);
            return;
        }
        
        $order->match_time = time();
        $order->save();
        
        $orderModel->match($order,json_decode($order->query,true));
        
        Pro::unlock('build_order_' . $orderid );
        
    }
}
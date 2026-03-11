<?php
namespace app\queue;

use os\AsyncQueue;
use app\api\traits\Pro;

class OrderNotify
{
    use \app\queue\QueueTrait;
    //\os\AsyncQueue::pushTask('usera', ['foo' => 'bar'],3);
    protected $mode = 'compete'; // 或 'compete'/broadcast
    protected $task = 'OrderNotify';      // 业务/任务名
    
    // 重试配置
    protected $maxRetries = 3;     // 最大重试次数
    protected $retryInterval = 5;  // 重试间隔（秒）
    
    public function ok($data)
    {
        Pro::console('开始回调');
        // 获取队列数据
        $orderid = $data['data'];
        
        $order = Pro::model('Order')->where(['orderid' => $orderid ,'status' => 10])->find();
        
        $order->notify_number += 1;
        
        if(!$order['notify_info']['notify_url']){
            $order->notify_result = '无需回调';
            $order->save();
            return;
        }
        
        if($order['notify_number'] > 5){
            Pro::console('回调超过次数');
            $order->save();
            return;
        }
        $body = [
            'orderid'  => $order['orderid'],
            'suporder' => $order['suporder'],
            'appid'    => $order['notify_info']['appid'],
            'fee'      => $order['fee'],
            'status'   => $order['status'],
            'timestamp'=> time(),
        ];
        
        $body['sign'] = Pro::sign(
            $order['notify_info']['appid'].
            $order['notify_info']['appkey'].
            $order['suporder'].
            $body['timestamp']
            );
        $http = http_post([
                'url'  => $order['notify_info']['notify_url'],
                'body' => $body
            ]);
        
        if($http){
            Pro::console('回调成功');
            $notify_result = $http == 'ok' ? 'ok' : '其它';
            $order->notify_result = $notify_result;
            $order->save();
            return;
        }
        
        $order->save();
        
        $interval = $order['notify_number'] * 5;
        
        Pro::console($interval.'秒后再次回调');
        
        \os\AsyncQueue::pushTask('OrderNotify', $orderid,$interval);
        
    }
}
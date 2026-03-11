<?php

namespace app\queue\yunos;

use SolarSeahorse\WebmanRedisQueue\Consumer;
use SolarSeahorse\WebmanRedisQueue\Interface\ConsumerMessageInterface;
use app\api\model\Order as OrderModel;

class Notify extends Consumer
{
    // 连接标识，对应config/plugin/solarseahorse/webman-redis-queue/redis.php的配置
    protected string $connection = 'default';
    
    protected $system = null;
    
    public function init()
    {
        $this->system  = new \app\api\model\System;
    }

    // 消费
    public function consume(ConsumerMessageInterface $consumerMessage)
    {
        $this->init();
        
        // 获取队列数据
        $orderid = $consumerMessage->getData();
        
        $this->system->console('开始回调');
        
        $order = OrderModel::where(['orderid' => $orderid ,'status' => 10])->find();
        
        if(!$order['notify_info']['notify_url']){
            $order->notify_result = '无需回调';
            $order->save();
            return;
        }
        
        $order->notify_number += 1;
        
        if($order['notify_number'] > 3){
            $this->system->console('回调超过次数');
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
        
        $body['sign'] = $this->system->sign(
            $order['notify_info']['appid'].
            $order['notify_info']['appkey'].
            $order['suporder'].
            $body['timestamp']
            );
        $http = http_post([
                'url'  => $order['notify_info']['notify_url'],
                'body' => $body
            ]);
        
        if(!empty($http)){
            $this->system->console('回调成功');
            $notify_result = $http == 'ok' ? 'ok' : '其它';
            $order->notify_result = $notify_result;
            $order->save();
            return;
        }
        
        $order->save();
        
        $interval = $order['notify_number'] * 5;
        
        $this->system->console($interval.'秒后再次回调');
        Client::send('notify', $orderid,$interval);
        

    }
}
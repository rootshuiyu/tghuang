<?php

namespace app\queue\yunos;

use SolarSeahorse\WebmanRedisQueue\Consumer;
use SolarSeahorse\WebmanRedisQueue\Interface\ConsumerMessageInterface;
use app\api\model\Order as OrderModel;

class Query extends Consumer
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
        
        $order = OrderModel::where('orderid',$orderid)->find();
        
        if(!$order){
            return;
        }
        
        if($order->status != 2){
            return;
        }
        
        if(time() > $order['exptime']){
            $this->system->console('订单超时');
            $order->status = 3;
            $order->hash = null;
            $order->save();
            return;
        }
        
        $this->system->console('正在查单'.$order['exptime'] - time());
        
        $app = $this->system->moduleInstance($order['access']['module']);
        $result = $app->queryOrder($order);
        

    }
}
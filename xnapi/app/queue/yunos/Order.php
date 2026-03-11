<?php

namespace app\queue\yunos;

use SolarSeahorse\WebmanRedisQueue\Consumer;
use SolarSeahorse\WebmanRedisQueue\Interface\ConsumerMessageInterface;

use app\api\model\Order as OrderModel;

class Order extends Consumer
{
    // 连接标识，对应config/plugin/solarseahorse/webman-redis-queue/redis.php的配置
    protected string $connection = 'default';
    
    protected $system = null;
    protected $order = null;
    
    public function init()
    {
        $this->system  = new \app\api\model\System;
        $this->order  = new \app\api\model\Order;
        
    }

    // 消费
    public function consume(ConsumerMessageInterface $consumerMessage)
    {
        $this->init();
        
        // 获取队列数据
        $orderid = $consumerMessage->getData();

        $order = OrderModel::where(['orderid' => $orderid , 'status' => 0])->find();
        
        if(!$order){
            $this->system->console('订单不存在');
            return;
        }
        
        $this->system->console('正在匹配订单'. $order['exptime'] - time());
        
        $this->order->match($order,json_decode($order->query,true));

    }
}
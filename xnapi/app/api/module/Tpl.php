<?php
namespace app\api\module;

use support\Request;
use app\api\traits\Pro;
use think\facade\Db;

class Taobaoecard
{

    public $access_id   = 20;
    public $query_delay = 5;
    
    public function build($order,$account)
    {
        
        $trade = $this->tradeOrder($order,$account);
        if(!$trade){
            return false;
        }

        $syorder = $trade['syorder'];
        $payurl  = $trade['payurl'];
        
        $exResult = Pro::model('Order')->exOrder(
            $order['orderid'],
            $account,
            $payurl,
            $syorder
            );
        
        return $exResult;
        
        
    }
    
    public function tradeOrder($order,$account)
    {
        
        return ['syorder' => 123456 , 'payurl' => 111233];
        
    }
    
    public function queryOrder($order)
    {
        //Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1
        //订单状态 0=正在取码，1=取码失败，2=等待支付，3=支付超时，10=支付成功

    }
    
    public function queryTradeOrder($ck,$money,$crTimesmap)
    {

        
        
    }
    
    public function bind_callback($post)
    {
        
        
    }

}
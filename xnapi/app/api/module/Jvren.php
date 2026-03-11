<?php
namespace app\api\module;

use support\Request;
use app\api\traits\Pro;
use think\facade\Db;

class Jvren
{

    public $access_id   = 40;
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

    public function goodids($order,$account)
    {
        
        $http = Pro::http()
            ->url('https://jbg.ztgame.com/goods-item/list?page=1&limit=5&status=online&zone_id='. $account['value1'] .'&game_id=1&label=POINT_CARD&sort=%5B%7B%22property%22%3A%22price%22%2C%22direction%22%3A%22ASC%22%7D%2C%7B%22property%22%3A%22on_sale_cnt%22%2C%22direction%22%3A%22DESC%22%7D%2C%7B%22property%22%3A%22display_order%22%2C%22direction%22%3A%22DESC%22%7D%2C%7B%22property%22%3A%22id%22%2C%22direction%22%3A%22ASC%22%7D%5D')
            ->method('GET')
            ->header('User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.110 Safari/537.36
            Authorization: Bearer '. $account['ck'] .'
            Content-Type: application/x-www-form-urlencoded; charset=UTF-8
            Referer: https://jbg.ztgame.com/client/item/index.html')
            ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('获取道具列表结果',$http->getBody());
        if(!$http->getBody()){
            return null;
        }
        $data = $http->getJson();
        if(isset($data['name'])){
            if($data['name'] == 'Unauthorized' ){
                Pro::model('Account')->offline($account['id'],'ck过期');
                return [];
            }
        }
        
        if(!isset($data['data']['rows']  )){
            return null;
        }
        
        $value = null;
        foreach ($data['data']['rows'] as $k => $v){
            /*if($k == 0){
                continue;
            }*/
            if($v['item_name'] == '1元点数卡'){
                $value = $v['goods_item_id'];
                //break;
            }
            
        }
        
        return $value;
        
    }
    
    public function tradeOrder($order,$account)
    {
        $goodsid = $this->goodids($order,$account);
        if(!$goodsid){
            return [];
        }
        $amount = (int) $order['fee'] * 2;
        $http = Pro::http()
            ->url('https://jbg.ztgame.com/order-item/create')
            ->method('POST')
            ->header('User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.110 Safari/537.36
            Authorization: Bearer '. $account['ck'] .'
            Content-Type: application/x-www-form-urlencoded; charset=UTF-8
            Referer: https://jbg.ztgame.com/client/item/index.html')
            ->body('goods_item_id='. $goodsid .'&amount='. $amount .'&return_url=https%3A%2F%2Fjbg.ztgame.com%2Fclient%2Fitem%2Fpayresult.html')
            ->lsp_jl($order['ip'])
            ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('接口下单结果',$http->getBody());
        if(!$http->getBody()){
            return [];
        }
        $data = $http->getJson();
        
        if(isset($data['name'])){
            if($data['name'] == 'Unauthorized' ){
                Pro::model('Account')->offline($account['id'],'ck过期');
                return [];
            }
        }

        if(!isset($data['data']['order_id'])){
            return [];
        }
    
        $syorder = $data['data']['order_id'];
        $initUrl = $data['data']['pay_url'];
        $payid   = $order['pay_type'] == 'alipay' ? 1 : 2;
    
        $payurl = $this->init($order,$initUrl, $payid );
        if(!$payurl){
            return [];
        }
        
        return ['syorder' => $syorder , 'payurl' => ['url' => $payurl, 'qrcode' => '' ]];
        
    }
    
    public function init($order,$initUrl, $payid ){

        $url = str_ireplace("https://cbgpay.ztgame.com/v2/qrcode.html", "", $initUrl);
    
        $http = Pro::http()->url('https://cbgpay.ztgame.com/v2/app/init' . $url)->method('GET')->lsp_jl($order['ip'])->ex();
        Pro::logger('order')->resid($order['orderid'])->info('接口init',$http->getBody());
        if(!$http->getBody()){
            return null;
        }
    
        $data = $http->getJson();
    
        if(!isset($data['data']['token'])){
            return null;
        }
    
        $pay = $this->pay($order,$data['data']['token'],$payid);
        return $pay;
    }
    
    
    public function pay($order , $token , $payid){
    
        $http = Pro::http()
                ->url('https://cbgpay.ztgame.com/v2/pay')
                ->method('POST')
                ->formData([
                    'payid' => $payid,
                    'token' => $token
                ])
                ->lsp_jl($order['ip'])
                ->ex();
                
        Pro::logger('order')->resid($order['orderid'])->info('接口pay',$http->getBody());
        if(!$http->getBody()){
            return null;
        }
        $data = $http->getJson();
        if(!isset($data['data']['url'])){
            return null;
        }
        
        $this->queryh($order,$data['data']['payin_order_id'],$token);

        if($payid == 1){
            $url = $data['data']['url'] . '&t=' . $data['data']['t'];
        }else{
            $url = $data['data']['url'] . '&t=' . $data['data']['t'] . '&wx_app_id='. $data['data']['wx_app_id'] .'&state=' . $data['data']['state'];
        }
    
        $payurl = $this->getPay($order , $url);
        if(!$payurl){
            for ($i = 0; $i < 3; $i++) {
                $payurl = $this->pay($order , $token , $payid);
                if($payurl){
                    break;
                }
            }
            
        }
        return $payurl;
    }
    
    public function queryh($order,$syorder,$token){
        
        $http = Pro::http()
                ->url('https://cbgpay.ztgame.com/v2/pay/query')
                ->method('POST')
                ->formData([
                    'orderid' => $syorder,
                    'token'   => $token
                ])
                ->lsp_jl($order['ip'])
                ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('接口query',$http->getBody());
        
        Pro::model('Order')->setOrderData($order['orderid'],['ext' => json_encode([
                    'orderid' => $syorder,
                    'token'   => $token
                ])]);
 
    }

    public function getPay($order,$url){
        
        for ($i = 0; $i < 2; $i++) {
            $url1 = Pro::http()->url($url)->method('GET')->ex()->getHeader('Location');
            if($url1){
                break;
            }
        }

        Pro::logger('order')->resid($order['orderid'])->info('获取第一次链接',$url1);
        if(!$url1){
            return null;
        }
        
        return $url1;
        
        for ($i = 0; $i < 3; $i++) {
            $url2 = Pro::http()->url($url1)->method('GET')->ex()->getHeader('Location');
            if($url2){
                break;
            }
        }
        
        Pro::logger('order')->resid($order['orderid'])->info('获取第二次链接',$url2);
        
        if(!$url2){
            return null;
        }
        
    }
    
    public function queryOrder($order)
    {
        //Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1
        //订单状态 0=正在取码，1=取码失败，2=等待支付，3=支付超时，10=支付成功
        $info = json_decode($order['ext'],true);
        $http = Pro::http()
                ->url('https://cbgpay.ztgame.com/v2/pay/query')
                ->method('POST')
                ->formData([
                    'orderid' => $info['orderid'],
                    'token'   => $info['token']
                ])
                ->lsp_jl($order['ip'])
                ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('接口query',$http->getBody());
        
       $data = $http->getJson();
       
       if(!isset($data['data']['ret'])){
           return false;
       }
       
       if($data['data']['ret'] == 1001){
           Pro::model('Order')->settle($order['orderid']);
           return true;
       }
        
        return;
        $account = $order['account'];
        $http = Pro::http()
            ->url('https://jbg.ztgame.com/order-item/purchased-list?page=1&limit=10&game_id=1&zone_id='. $account['value1'] .'&category=item')
            ->method('GET')
            ->header('User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.110 Safari/537.36
            Authorization: Bearer '. $account['ck'] .'
            Content-Type: application/x-www-form-urlencoded; charset=UTF-8
            Referer: https://jbg.ztgame.com/client/item/purchase.html')
            ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('回调结果',$http->getBody());
        if(!$http->getBody()){
            return false;
        }
        
        $data = $http->getJson();
        if(!isset($data['data']['rows'])){
            return false;
        }
        
        $result = false;
        foreach ($data['data']['rows'] as $v){
            if($v['sale_order_id'] == $order['syorder']){
                if($v['order_status'] == 'checked'){
                    $result = true;
                    Pro::model('Order')->settle($order['orderid']);
                }
                break;
            }
        }
        
        return $result;

    }
    
    public function http_query_order($data = null)
    {
        
        $order = Pro::model('Order')->where('orderid',$data['orderid'])->find();
        if(!$order){
            return '订单不存在';
        }
        $payResult = $this->queryOrder($order);
        
        return $payResult ? '已支付' : '未支付';
        
    }
    
    public function queryTradeOrder($ck,$money,$crTimesmap)
    {

        
        
    }
    
    public function bind_callback($post)
    {
        
        
    }

}
<?php
namespace app\api\module;

use support\Request;
use app\api\traits\Pro;
use think\facade\Db;
use Bilulanlv\ThinkCache\facade\ThinkCache;

class Jvrencz
{

    public $access_id   = 41;
    public $query_delay = 5;
    
    public function build($order,$account)
    {
        
        $trade = $this->tradeOrder($order,$account);
        if(!$trade){
            return false;
        }
        
        Pro::model('Account')->online($account['id']);

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
    
    public function tokens($str)
    {
        
        $key = sha1($str);
        $cache = ThinkCache::get($key);
        if($cache){
            return $cache;
        }
        
        $info = queryStr($str);
        $http = Pro::http()
            ->url('https://pay.ztgame.com/v5/client/user/login-by-token')
            ->method('POST')
            ->header('
                User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Mobile Safari/537.36
                Referer: ' . $str)
            ->formData([
                'uid'   => $info['uid'],
                'token' =>  $info['token'],
            ])
            ->ex();
        
        if(!$http->getBody()){
            return [];
        }
        
        $data = $http->getJson();
        
        if(!isset($data['data']['Authorization'] )){
            return [];
        }
        
        $data = [
            'auth'    =>  $data['data']['Authorization'] ,
            'account' =>  $data['data']['member']['show_account']
        ];
        
        ThinkCache::set($key,$data,1000);
        
        return $data;
        /*
        
        {
    "succ": true,
    "msg": "",
    "data": {
        "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6IjRmMWcyM2ExMmFhIn0.eyJpc3MiOiJodHRwczpcL1wvenRnYW1lLmNvbSIsImF1ZCI6Imh0dHBzOlwvXC96dGdhbWUub3JnIiwianRpIjoiNGYxZzIzYTEyYWEiLCJpYXQiOjE3Njk5Mzg3MDAsImV4cCI6MTc2OTk0MDUwMCwidWlkIjo3NDk5ODI4OTksImdpZCI6IjEiLCJ6aWQiOiIxNTkzIiwiY2lkIjoiMjc5MDgiLCJpcCI6Mjg3NjU3ODU4MCwicm9sZW5hbWUiOiJcdTUzNDNcdTY3ZDJERFx1NzA2YyJ9.oX6HaKiKItCVNc_T5hgiiAs1k6DlGPzGb3Su6k6G6sI",
        "member": {
            "show_account": "15735100756",
            "game_id": "1",
            "zone_id": "1593",
            "zone_name": "万人返利专区",
            "char_name": "千柒DD灬"
        },
        "game": {
            "game_name": "征途",
            "unit1": "点",
            "unit2": ""
        },
        "params": {
            "alipay_discounts": true,
            "alipay_discounts_desc": "支付宝享限时优惠"
        }
    }
}
        */
        
    }
    
    public function tradeOrder($order,$account)
    {
        
        if($order['pay_type'] == 'alipay'){
          
          return  $this->tradeAlipay($order,$account);
        }
        
        return  $this->wxPay($order,$account);
        
    }
    
    public function tradeAlipay($order,$account)
    {
        $money = (int) $order['fee'];
        $tokens = $this->tokens($account['ck']);
        if(!$tokens){
            return [];
        }
        $http = Pro::http()
            ->url('https://pay.ztgame.com/v5/official/item/zt')
            ->method('POST')
            ->header('
            Authorization: '. $tokens['auth'] .'
                User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Mobile Safari/537.36
                Referer: ' . $account['ck'])
            ->formData([
                'money'   => $money,
                'channel' =>  'alipayQrc',
                'account' => $tokens['account'],
                'source'  => 'client',
                'type'    => 'item'
            ])
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

        $syorder = $data['data']['order_id'];
        $payurl  = $data['data']['qrcode'];
        
        return ['syorder' => $syorder , 'payurl' => ['url' => $payurl, 'qrcode' => '' ]]; 
        
    }
    public function wxPay($order,$account)
    {
        
        $money = (int) $order['fee'];
        $tokens = $this->tokens($account['ck']);
        if(!$tokens){
            return [];
        }
        $http = Pro::http()
            ->url('https://pay.ztgame.com/v5/official/item/zt')
            ->method('POST')
            ->header('
            Authorization: '. $tokens['auth'] .'
                User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Mobile Safari/537.36
                Referer: ' . $account['ck'])
            ->formData([
                'money'   => $money,
                'channel' =>  'wxH5',
                'account' => $tokens['account'],
                'source'  => 'client',
                'return_url' => 'https://pay.ztgame.com/v5/client/payResult/',
                'quit_url'   => 'https://pay.ztgame.com/v5/client/payResult/',
                'type'    => 'item'
            ])
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

        $syorder = $data['data']['order_id'];
        $payurl  = $this->getwxurl($data['data']['htmlData'],$order);
        if(!$payurl){
            return [];
        }
        
        return ['syorder' => $syorder , 'payurl' => ['url' => $payurl, 'qrcode' => '' ]]; 
        
    }
    
    public function getwxurl($url,$order)
    {
        
        $success = false; $httpNumber = 0;
        while(!$success && $httpNumber < 3){
            
            $http = Pro::http()
                ->url($url)
                ->method('GET')
                ->header('sec-ch-ua: ""
                    sec-ch-ua-mobile: ?1
                    sec-ch-ua-platform: "iPhone"
                    Upgrade-Insecure-Requests: 1
                    DNT: 1
                    User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1
                    Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
                    Sec-Fetch-Site: cross-site
                    Sec-Fetch-Mode: navigate
                    Sec-Fetch-User: ?1
                    Sec-Fetch-Dest: document
                    Referer: https://pay.ztgame.com/
                    Accept-Language: zh-CN,zh;q=0.9')
                //->lsp_jl($order['ip'])
                ->ex();
                
            $httpNumber++;
            if($http->getBody()){
                $success = true;
                break;
            }
            
        }
        
        //Pro::logger('order')->resid($order['orderid'])->info('获取微信链接结果',$http->getBody());

        if(!$http->getBody()){
            Pro::logger('order')->resid($order['orderid'])->error('获取微信支付链接失败');
            return null;
        }
        $str = $http->getBody();
        // 匹配微信支付URL的正则表达式
        $pattern = '/weixin:\/\/wap\/pay\?[^"\']+/';
        
        // 执行匹配
        if (preg_match($pattern, $str, $matches)) {
            Pro::logger('order')->resid($order['orderid'])->success('获取微信支付链接成功');
            return $matches[0];
        }
        
        Pro::logger('order')->resid($order['orderid'])->error('获取微信支付链接失败');
        
        return null;
        
    }
    
    public function queryOrder($order)
    {
        //Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1
        //订单状态 0=正在取码，1=取码失败，2=等待支付，3=支付超时，10=支付成功

        $tokens = $this->tokens($order['account']['ck']);
        $http = Pro::http()
            ->url('https://pay.ztgame.com/v5/client/order/query?order_id=' . $order['syorder'])
            ->method('GET')
            ->header('User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.110 Safari/537.36
            Authorization: '. $tokens['auth'] .'
            Content-Type: application/x-www-form-urlencoded; charset=UTF-8
            Referer: https://pay.ztgame.com')
            ->lsp_tps()
            ->ex();
            
        Pro::logger('order')->resid($order['orderid'])->info('接口query',$http->getBody());
        
       $data = $http->getJson();
       if(!isset($data['data']['result'])){
           return false;
       }

       if($data['data']['result'] == 1){
           Pro::model('Order')->settle($order['orderid']);
           return true;
       }

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
    
    public function test1()
    {

        $http = Pro::http()
            ->url('https://pay.ztgame.com/v5/official/item/zt')
            ->method('POST')
            ->header('
            Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6IjRmMWcyM2ExMmFhIn0.eyJpc3MiOiJodHRwczpcL1wvenRnYW1lLmNvbSIsImF1ZCI6Imh0dHBzOlwvXC96dGdhbWUub3JnIiwianRpIjoiNGYxZzIzYTEyYWEiLCJpYXQiOjE3Njk5Mzc3NzMsImV4cCI6MTc2OTkzOTU3MywidWlkIjo3NDk5ODI4OTksImdpZCI6IjEiLCJ6aWQiOiIxNTkzIiwiY2lkIjoiMjc5MDgiLCJpcCI6Mjg3NjU3ODU4MCwicm9sZW5hbWUiOiJcdTUzNDNcdTY3ZDJERFx1NzA2YyJ9.WaExXa8ZGDm_fg-nqVJYusGh3iDgElB_p0CokX-Uvbk
                User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Mobile Safari/537.36
                Referer: https://pay.ztgame.com/v5/client/?game_id=1&token=674b8bb6fcd38011fe1effe036578cea&uid=749982899&type=item&item_id=0&showDesc=0')
            ->formData([
                'money' => 100,
                'channel' =>  'alipayH5',
                'account' => '15735100756',
                'source' => 'client',
                'quit_url' => 'https://pay.ztgame.com/v5/client/?game_id=1&token=674b8bb6fcd38011fe1effe036578cea&uid=749982899&type=item&item_id=0&showDesc=0',
                'return_url' => 'https://pay.ztgame.com/v5/client/?game_id=1&token=674b8bb6fcd38011fe1effe036578cea&uid=749982899&type=item&item_id=0&showDesc=0',
                'type' => 'item'
            ])
            ->lsp_jl('171.117.27.20')
            ->ex();
        return $http->getBody();
        
    }
    
    public function test()
    {

        $http = Pro::http()
            ->url('https://pay.ztgame.com/v5/official/item/zt')
            ->method('POST')
            ->header('
            Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6IjRmMWcyM2ExMmFhIn0.eyJpc3MiOiJodHRwczpcL1wvenRnYW1lLmNvbSIsImF1ZCI6Imh0dHBzOlwvXC96dGdhbWUub3JnIiwianRpIjoiNGYxZzIzYTEyYWEiLCJpYXQiOjE3Njk5Mzc3NzMsImV4cCI6MTc2OTkzOTU3MywidWlkIjo3NDk5ODI4OTksImdpZCI6IjEiLCJ6aWQiOiIxNTkzIiwiY2lkIjoiMjc5MDgiLCJpcCI6Mjg3NjU3ODU4MCwicm9sZW5hbWUiOiJcdTUzNDNcdTY3ZDJERFx1NzA2YyJ9.WaExXa8ZGDm_fg-nqVJYusGh3iDgElB_p0CokX-Uvbk
                User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Mobile Safari/537.36
                Referer: https://pay.ztgame.com/v5/client/?game_id=1&token=674b8bb6fcd38011fe1effe036578cea&uid=749982899&type=item&item_id=0&showDesc=0')
            ->formData([
                'money' => "100",
                'channel' =>  'alipayQrc',
                'account' => '15735100756',
                'source' => 'client',
                'type' => 'item'
            ])
            ->lsp_jl('171.117.27.20')
            ->ex();
        return $http->getBody();
        
    }
    
    public function testq()
    {
        
        $http = Pro::http()
            ->url('https://pay.ztgame.com/v5/client/order/query?order_id=317699220306245922')
            ->method('GET')
            ->header('User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.110 Safari/537.36
            Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6IjRmMWcyM2ExMmFhIn0.eyJpc3MiOiJodHRwczpcL1wvenRnYW1lLmNvbSIsImF1ZCI6Imh0dHBzOlwvXC96dGdhbWUub3JnIiwianRpIjoiNGYxZzIzYTEyYWEiLCJpYXQiOjE3Njk5MjE5OTksImV4cCI6MTc2OTkyMzc5OSwidWlkIjo3NDk5ODI4OTksImdpZCI6IjEiLCJ6aWQiOiIxNTkzIiwiY2lkIjoiMjc5MDgiLCJpcCI6Mjg3NjU3ODQwNCwicm9sZW5hbWUiOiJcdTUzNDNcdTY3ZDJERFx1NzA2YyJ9.DhaK8gfs1DR9TbV4KGlqux1u3viU_MECcXyMSJoU6sc
            Content-Type: application/x-www-form-urlencoded; charset=UTF-8
            Referer: https://pay.ztgame.com')
            ->ex();
        return $http->getBody();
        
    }
    
    public function test2(){
        $str = 'https://pay.ztgame.com/v5/client/?game_id=1&token=4924eab4f0a0951039bcb70a10225173&uid=749982899&type=item&item_id=0&showDesc=0';
        
        $info = queryStr($str);
        $http = Pro::http()
            ->url('https://pay.ztgame.com/v5/client/user/login-by-token')
            ->method('POST')
            ->header('
                User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Mobile Safari/537.36
                Referer: ' . $str)
            ->formData([
                'uid'   => $info['uid'],
                'token' =>  $info['token'],
            ])
            ->ex();
            
            return $http->getBody();
        
    }

}
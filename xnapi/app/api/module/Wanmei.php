<?php
namespace app\api\module;

use support\Request;
use app\api\traits\Pro;
use think\facade\Db;

class Wanmei
{

    public $access_id   = 36;
    public $query_delay = 5;
    
    public function build($order,$account)
    {
        /*$lockKey = "lock:order:{$account['id']}";
        $lock = Pro::exlock($lockKey,300);
        if(!$lock){
            Pro::logger('order')->resid($order['orderid'])->pending('账号正在等待中');
            return false;
        }*/
        
        if($order['fee'] <= 6){
            Pro::logger('order')->resid($order['orderid'])->error('订单金额不可小于6');
            return false;
        }
        
        $trade = $this->tradeOrder($order,$account);
        if(!$trade){
            return false;
        }

        $syorder = $trade['syorder'];
        $payurl  = $trade['payurl'];
        $ext     = $trade['ext'];
        
        $exResult = Pro::model('Order')->exOrder(
            $order['orderid'],
            $account,
            $payurl,
            $syorder,
            ['ext' => json_encode($ext)]
            );
        
        return $exResult;
        
        
    }
    
    public function tradeOrder($order,$account)
    {
        $ext = userAgent();
        $ext['cookie'] = $this->getCookie();
        
        $info = $account['config'];
        
        $fullTime = microtime(true);
        $time     = $fullTime - floor($fullTime);
        $username = $info['username'];
        $zone     = $info['zone'];
        $gametype = $info['gametype'];
        
        $e_event = Pro::http()
                 ->method('GET')
                 ->url('https://pay.wanmei.com/new//ajax.do?op=username&username='. $username .'&gametype='. $gametype .'&time='.$fullTime)
                 ->header('
                        Referer: https://pay.wanmei.com/new/newpay.do?op=prepay&gametype='. $gametype .'
                        Sec-Fetch-Dest: empty
                        Sec-Fetch-Mode: cors
                        Sec-Fetch-Site: same-origin
                        User-Agent: '. $ext['u'] .'
                        X-Requested-With: XMLHttpRequest
                        sec-ch-ua: "Not)A;Brand";v="24", "Chromium";v="'. $ext['v'] .'"
                        sec-ch-ua-mobile: ?0
                        sec-ch-ua-platform: "Windows"
                        Cookie:'. $ext['cookie'])
                 ->lsp_jl($order['ip'])
                 ->ex();
        
        $e_event = Pro::http()
                 ->method('GET')
                 ->url('https://pay.wanmei.com/new//ajax.do?op=captchasInit&r='.$time)
                 ->header('
                        Referer: https://pay.wanmei.com/new/newpay.do?op=prepay&gametype='. $gametype .'
                        Sec-Fetch-Dest: empty
                        Sec-Fetch-Mode: cors
                        Sec-Fetch-Site: same-origin
                        User-Agent: '. $ext['u'] .'
                        X-Requested-With: XMLHttpRequest
                        sec-ch-ua: "Not)A;Brand";v="24", "Chromium";v="'. $ext['v'] .'"
                        sec-ch-ua-mobile: ?0
                        sec-ch-ua-platform: "Windows"
                        Cookie:'. $ext['cookie'])
                 ->lsp_jl($order['ip'])
                 ->ex();

        if(!$e_event->getBody()){
            Pro::logger('order')->resid($order['orderid'])->error('获取验证码叁数失败');
            return [];
        }
        $json = $e_event->getJson();
        if(!isset($json['result'])){
            Pro::logger('order')->resid($order['orderid'])->error('获取验证码叁数失败');
            return [];
        }

        Pro::logger('order')->resid($order['orderid'])->success('过验证成功');
        
        $capTicket = $json['result'];
        
        if($order['pay_type'] == 'alipay'){//支付宝
            $paytype = '16001';
            $payway = 'yinhangka';
            $paytext = '支付宝扫码';
            $payAppName = '支付宝';
        }else if($order['pay_type'] == 'wxpay'){//微信
            $paytype = '19001';
            $payway = 'yinhangka';
            $paytext = '微信支付';
            $payAppName = '微信';
        }
        if($gametype == '1'){
            $exchange = '150|分钟';
        }else if($gametype == '3'){
            $exchange = '1|黄金';
        }else if($gametype == '16'){
            $exchange = '40|元宝';
        }
        
        $body = 'gametype='.$gametype.'&exchange='.$exchange.'&preOrderSn=&username='.$username.'&username2='.$username.'&zone='.$zone.'&paytype='.$paytype.'&payway='.$payway.'&paytext='.urlencode($paytext).'&mobery=&qrcode=1&payAppName='.urlencode($payAppName).'&cardnumber=&cardpasswd=&sxcardnumber=&sxcardpasswd=&ivrcardnumber=&ivrcardpasswd=&dxcardnumber=&dxcardpasswd=&rand=&capTicket='.$capTicket.'&money='.round($order['fee']);
        
        //print_r($body);return[];
        
        
        $http = Pro::http()
                 ->method('POST')
                 ->url('https://pay.wanmei.com/new/newpay.do?op=pay')
                 ->header('
                        Origin: https://pay.wanmei.com
                        Referer: https://pay.wanmei.com/new/newpay.do?op=prepay&gametype='. $gametype .'
                        Sec-Fetch-Dest: empty
                        Sec-Fetch-Mode: cors
                        Sec-Fetch-Site: same-origin
                        User-Agent: '. $ext['u'] .'
                        X-Requested-With: XMLHttpRequest
                        sec-ch-ua: "Not)A;Brand";v="24", "Chromium";v="'. $ext['v'] .'"
                        sec-ch-ua-mobile: ?0
                        sec-ch-ua-platform: "Windows"
                        Cookie:'. $ext['cookie'])
                 ->body($body)
                 ->lsp_jl($order['ip'])
                 ->ex();
        
        Pro::logger('order')->resid($order['orderid'])->info('接口下单结果',$http->getBody());
        if(!$http->getBody()){
            Pro::logger('order')->resid($order['orderid'])->error('接口下单失败');
            return [];
        }
        
        $data = $http->getJson();
        
        if(isset($data['status'])){
            if($data['status'] == 0){
                Pro::model('Account')->online($account['id']);
            }
            if($data['status'] != 0){
                Pro::model('Account')->offline($account['id'],$data['message']);
            }
        }
        
        if(!isset($data['redirectUrl'])){
            Pro::logger('order')->resid($order['orderid'])->error('数据解析错误');
            return [];
        }
        $paystr = queryStr($data['redirectUrl'],'url');
        
        $payurl = ''; $qrcode = '';
        if($order['pay_type'] == 'alipay'){
            $payurl = $paystr;
        }else{
            $qrcode = $paystr;
        }
        
        return [
            'syorder' => $data['ordernumber'] , 
            'payurl' => ['url' => $payurl, 'qrcode' => $qrcode ],
            'ext'    => $ext
        ];
        
    }
    
    public function getCookie() {
        $currentTime = time();
    
        // 生成随机十六进制字符串
        $randomHex32 = '';
        $chars = '0123456789abcdef';
        for ($i = 0; $i < 32; $i++) {
            $randomHex32 .= $chars[rand(0, 15)];
        }
    
        $randomHex16 = '';
        for ($i = 0; $i < 16; $i++) {
            $randomHex16 .= $chars[rand(0, 15)];
        }
    
        $randomHex8 = '';
        for ($i = 0; $i < 8; $i++) {
            $randomHex8 .= $chars[rand(0, 15)];
        }
    
        // 构建 cookie 字符串
        $cookie = sprintf(
            'JSESSIONID=%s; ' .
            '__mtxud=%s.%d.%d.%d.1; ' .
            '__mtxsr=csr:(direct)|cdt:(direct)|advt:(none)|camp:(none); ' .
            '__mtxcar=(direct):(none); ' .
            'Hm_lvt_ced744dfae7a0fe07aadbd98133e242b=%d; ' .
            'Hm_lpvt_ced744dfae7a0fe07aadbd98133e242b=%d; ' .
            'HMACCOUNT=%s; ' .
            'puclic_hg_flag2=true; ' .
            'wmCurrGame=%%7B%%22id%%22%%3A%d%%2C%%22name%%22%%3A%%22%%E6%%9C%%AA%%E9%%80%%89%%E6%%8B%%A9%%22%%2C%%22icon%%22%%3A%%22%%22%%2C%%22type%%22%%3A%%22client%%22%%2C%%22hot%%22%%3A%d%%2C%%22wanmei_login%%22%%3A%d%%2C%%22qq_login%%22%%3A%d%%2C%%22wechat_login%%22%%3A%d%%2C%%22weibo_login%%22%%3A%d%%2C%%22services%%22%%3A%d%%2C%%22hide%%22%%3A%d%%2C%%22bg%%22%%3A%%22%%22%%7D; ' .
            'Hm_lvt_4389c553609aacc5ded73fe148a82e8b=%d; ' .
            '__mtxsd=%s.%d.%d.%d; ' .
            'Hm_lpvt_4389c553609aacc5ded73fe148a82e8b=%d',
    
            // JSESSIONID
            $randomHex32,
    
            // __mtxud
            $randomHex16,
            $currentTime * 1000,
            $currentTime * 1000,
            $currentTime * 1000,
    
            // Hm_lvt_ced744dfae7a0fe07aadbd98133e242b
            $currentTime,
            // Hm_lpvt_ced744dfae7a0fe07aadbd98133e242b
            $currentTime,
    
            // HMACCOUNT
            strtoupper($randomHex16),
    
            // wmCurrGame 参数（随机化ID和布尔值）
            rand(1, 99999),
            rand(0, 100),
            rand(0, 1),
            rand(0, 1),
            rand(0, 1),
            rand(0, 1),
            rand(0, 1),
            rand(0, 1),
    
            // Hm_lvt_4389c553609aacc5ded73fe148a82e8b
            $currentTime,
    
            // __mtxsd
            $randomHex8,
            $currentTime * 1000,
            rand(1000, 9999),
            rand(1, 10),
    
            // Hm_lpvt_4389c553609aacc5ded73fe148a82e8b
            $currentTime
        );
    
        return $cookie;
    }
    
    public function queryOrder($order)
    {
        //订单状态 0=正在取码，1=取码失败，2=等待支付，3=支付超时，10=支付成功
        $ext = json_decode($order['ext'],true);
        
        $info = $order['account']['config'];
        
        $fullTime = microtime(true);
        $time = $fullTime - floor($fullTime);
        $gametype = $info['gametype'];
        
        $url = 'https://pay.wanmei.com/new//paycheck.do?ordernumber='.$order['syorder'].'&time='.$time;
        $http = Pro::http()
                 ->method('GET')
                 ->url($url)
                 ->header('
                        Origin: https://pay.wanmei.com
                        Referer: https://pay.wanmei.com/new/newpay.do?op=prepay&gametype='. $gametype .'
                        Sec-Fetch-Dest: empty
                        Sec-Fetch-Mode: cors
                        Sec-Fetch-Site: same-origin
                        User-Agent: '. $ext['u'] .'
                        X-Requested-With: XMLHttpRequest
                        sec-ch-ua: "Not)A;Brand";v="24", "Chromium";v="'. $ext['v'] .'"
                        sec-ch-ua-mobile: ?0
                        sec-ch-ua-platform: "Windows"
                        Cookie:'. $ext['cookie'])
                 ->lsp_tps()
                 ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('回调查询接口', $url );
        $status = $http->getBody();
        Pro::logger('order')->resid($order['orderid'])->info('回调查询结果',"$status");
        if($http->getBody() == ''){
            Pro::logger('order')->resid($order['orderid'])->error('回调查询失败');
            return false;
        }
        
        if($http->getBody() === '0'){
            Pro::logger('order')->resid($order['orderid'])->info('回调查询-已支付');
            Pro::model('Order')->settle($order['orderid']);
            return true;
        }
        Pro::logger('order')->resid($order['orderid'])->info('回调查询-未支付');
        return false;
        
        

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
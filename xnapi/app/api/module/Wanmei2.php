<?php
namespace app\api\module;

use support\Request;
use app\api\traits\Pro;
use think\facade\Db;

class Wanmei2
{

    public $access_id   = 39;
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
        $info = $account['config'];
        
        $username = $info['username'];
        $gametype = $info['gametype'];
        $zone     = $info['zone'];
        $chargeAmount = (int) $order['fee'];
        $payid = $order['pay_type'] == 'alipay' ? 16001 : 19001;
        
        $payApi = $order['pay_type'] == 'alipay' ? 'https://cpay.wanmei.com/chargeGame/thirdPay' : 'https://cpay.wanmei.com/chargeGame/qrcodePay';
        
        $body = 'gameId='. $gametype .'&serverId='. $zone .'&chargeAmount='. $chargeAmount .'&payTypeId='. $payid .'&toUserName='. $username .'&terminal=ArcWeb&showType=1';
        
        $http  =  Pro::http()
              ->url($payApi)
              ->header('Cookie: ' . $info['ck'])
              ->method('POST')
              ->body($body)
              ->lsp_jl($order['ip'])
              ->ex();

            Pro::logger('order')->resid($order['orderid'])->info('接口下单结果',$http->getBody());
            if(!$http->getBody()){
                Pro::logger('order')->resid($order['orderid'])->error('接口下单失败');
                return [];
            }
            
            $data = $http->getJson();
            
            if(!isset( $data['result']['orderId'] )){
                Pro::logger('order')->resid($order['orderid'])->error('数据解析错误');
                return [];
            }
            
        $qrcode = ''; $payurl = '';
        if($order['pay_type'] == 'alipay'){
            $payurl = $this->get_pay_url($order,$account,$data['result']['orderId']);
        }else{
            $payurl = $data['result']['payUrl'];
        }
        
        return [
            'syorder' => $data['result']['orderId'] , 
            'payurl' => ['url' => $payurl, 'qrcode' => $qrcode ]
        ];
        
        return ['syorder' => 123456 , 'payurl' => 111233];
        
    }
    
    public function get_pay_url($order,$account,$syorder){
        
        $info = $account['config'];
    
        $http  =  Pro::http()
                  ->url('https://cpay.wanmei.com/toGateway?payedOrderId=' . $syorder)
                  ->header('
                  User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Safari/537.36 SE 2.X MetaSr 1.0
                  Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
                  Sec-Fetch-Site: same-origin
                  Sec-Fetch-Mode: navigate
                  Sec-Fetch-User: ?1
                  Sec-Fetch-Dest: document
                  Referer: https://cpay.wanmei.com/
                  Cookie: ' . $info['ck'])
                  ->method('GET')
                  ->lsp_jl($order['ip'])
                  ->ex();
        $html = $http->getBody();
    
        $alipayData = $this->getAlipayData($html);
        
        Pro::logger('order')->resid($order['orderid'])->info('获取支付信息', json_encode($alipayData,256) );
    
        $url = $this->getRedirectUrl($alipayData['action'], 'biz_content=' . urlencode($alipayData['biz_content']));
    
        return $url;
    }
    
    public function getAlipayData($html) {
        // 定位第一个form标签及其action属性
        preg_match('/<form\b[^>]*\baction\s*=\s*("|\')([^"\']+)\1[^>]*>(.*?)<\/form>/is', $html, $formMatches);
        if (empty($formMatches)) {
            return [];
        }
        
        $action = $formMatches[2];
        $formContent = $formMatches[3];
        
        // 提取biz_content输入元素的值（保持原始格式，不解码HTML实体）
        // 优化正则表达式，使其更健壮地匹配各种HTML格式
        preg_match('/<input[^>]*?\sname\s*=\s*("|\')biz_content\1[^>]*?\svalue\s*=\s*("|\')(.*?)\2[^>]*?\/?>/i', $formContent, $bizContentMatch);
        
        // 如果第一次匹配失败，尝试不考虑name和value的顺序
        if (empty($bizContentMatch)) {
            preg_match('/<input[^>]*?\svalue\s*=\s*("|\')(.*?)\1[^>]*?\sname\s*=\s*("|\')biz_content\3[^>]*?\/?>/i', $formContent, $bizContentMatch);
            $bizContent = isset($bizContentMatch[2]) ? $bizContentMatch[2] : '';
        } else {
            $bizContent = isset($bizContentMatch[3]) ? $bizContentMatch[3] : '';
        }
        
        // 只返回action和biz_content的数组，不使用键名
        $singleEncoded = html_entity_decode($bizContent, ENT_QUOTES, 'UTF-8');
        //print_r($singleEncoded);die;
        return [
            'action' => $action,
            'biz_content' => $singleEncoded
        ];
    }

    public function getRedirectUrl( $url,  $body = ''){
        // 使用stream_context创建HTTP上下文
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Host: openapi.alipay.com',
                    'cache-control: max-age=0',
                    'upgrade-insecure-requests: 1',
                    'origin:https://cpay.wanmei.com',
                    'content-type: application/x-www-form-urlencoded',
                    'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                    'accept-language: en-US,en;q=0.9',
                    'sec-fetch-site: cross-site',
                    'sec-fetch-mode: navigate',
                    'sec-fetch-dest: document',
                    'referer: https://cpay.wanmei.com/',
                ]),
                'content' => $body,
                'follow_location' => false,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);
    
        // 发送第一次POST请求
        $response = @file_get_contents($url, false, $context);

    
        if ($response === false) {
            return $url;
        }

        // 获取第一次重定向URL和Cookie
        $redirectUrl = $url;
        $cookie1 = '';
    
        foreach ($http_response_header as $header) {
            if (preg_match('/^Location:\s*(.+)$/i', $header, $matches)) {
                $redirectUrl = trim($matches[1]);
            }
            if (preg_match('/^Set-Cookie:\s*(.+)$/i', $header, $matches)) {
                $cookie1 .= trim($matches[1]) . '; ';
            }
        }

        // 如果获取到重定向URL，使用GET方式再次请求
        if ($redirectUrl !== $url) {
            $context2 = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", [
                        'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'referer: ' . $url,
                        'cookie: ' . rtrim($cookie1, '; '),
                    ]),
                    'follow_location' => false,
                    'timeout' => 30,
                    'ignore_errors' => true,
                ],
            ]);
    
            $response2 = @file_get_contents($redirectUrl, false, $context2);
            
            // 获取第二次重定向URL和Cookie
            $finalUrl = $redirectUrl;
            $cookie2 = $cookie1;

            foreach ($http_response_header as $header) {
                if (preg_match('/^Location:\s*(.+)$/i', $header, $matches)) {
                    $finalUrl = trim($matches[1]);
                }
                if (preg_match('/^Set-Cookie:\s*(.+)$/i', $header, $matches)) {
                    $cookie2 .= trim($matches[1]) . '; ';
                }
            }
    
            // 获取最终页面的响应体
            $context3 = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", [
                        'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'referer: ' . $redirectUrl,
                        'cookie: ' . rtrim($cookie2, '; '),
                    ]),
                    'follow_location' => false,
                    'timeout' => 30,
                    'ignore_errors' => true,
                ],
            ]);
    
            $finalResponse = @file_get_contents($finalUrl, false, $context3);
    
            // 将响应体传入getQrcode方法获取最终返回值
            return $this->getQrcode($finalResponse ?: '');
        }
    
        return null;
    }

    public function getQrcode(string $html): string {
        // 使用正则表达式匹配name属性为"qrCode"的input标签及其value属性
        preg_match('/<input\s+[^>]*?\bname\s*=\s*["\']?qrCode["\']?[^>]*?\bvalue\s*=\s*["\']?([^"\'>]+)["\']?[^>]*?>/i', $html, $matches);
        
        // 如果找到匹配项，返回value属性值，否则返回空字符串
        return isset($matches[1]) ? $matches[1] : '';
    }
    
    public function queryOrder($order)
    {
        //Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1
        //订单状态 0=正在取码，1=取码失败，2=等待支付，3=支付超时，10=支付成功
        $info = $order['account']['config'];
        $http  =  Pro::http()
              ->url('https://cpay.wanmei.com/queryCurrUserOrderStatus')
              ->header('Cookie: ' . $info['ck'])
              ->method('POST')
              ->formData([
                  'orderId' => $order['syorder']
                ])
              ->lsp_tps()
              ->ex();

            Pro::logger('order')->resid($order['orderid'])->info('接口回调结果',$http->getBody());
            if(!$http->getBody()){
                Pro::logger('order')->resid($order['orderid'])->error('接口回调失败');
                return false;
            }
            
            $data = $http->getJson();
            //chargeStatus    payStatus
            if(!isset($data['result']['payStatus'])){
                return false;
            }
            
            if( $data['result']['payStatus'] == 1 ){
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
    
    public function queryTradeOrder($ck,$money,$crTimesmap)
    {

        
        
    }
    
    public function bind_callback($post)
    {
        
        
    }

}
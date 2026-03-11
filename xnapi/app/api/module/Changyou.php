<?php
namespace app\api\module;

use support\Request;
use app\api\traits\Pro;
use think\facade\Db;

class Changyou
{

    public $access_id   = 38;
    public $query_delay = 5;
    
    public function build($order,$account)
    {

        $trade = $this->tradeOrder($order,$account['config']);
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
    
    public function tradeOrder($order,$Json)
    {

        $title = [
            5073 => '天龙八部·归来',
            5057 => '怀旧天龙',
        ];

        $chnlType = $order['pay_type'] == 'alipay' ? 'alipay' : 'weixin';
        $chnl     = $order['pay_type'] == 'alipay' ? 235 : 221;

        $tradeNumber = $order['fee'] * 20;
        $tradeNumber = (int) $tradeNumber;
        $http = Pro::http()
                ->url('https://chong.changyou.com/tl/confirmCardOrders.do')
                ->method('POST')
                ->header('referer: https://chong.changyou.com/tl/tlBankInit.do?chnlType='. $chnlType .'
                    User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1')
                ->body(http_build_query([
                  'cardOrders.gameType' =>  $Json['gametype'],
                  'cardOrders.gameName' =>  $title[$Json['gametype']],
                  'gameType' => $Json['gametype'],
                  'chnl' => '',
                  'cardOrders.cardCount' => $tradeNumber,
                  'chnlType' => $chnlType,
                  'cardOrders.cardPwd' => 0,
                  'currentDiscount.discountRule' => $tradeNumber,
                  'userFrom' => 'ingame',
                  'orderCountInfo' => ',1',
                  'otherOpenWx' => '',
                  'cardOrders.cn' => $Json['username'],
                  'cardOrders.repeatcn' => $Json['username'],
                  'point_'.$tradeNumber => $tradeNumber,
                  'orderCount' => 1,
                  'cardOrders.chnl' => $chnl,
                  'costTime' => 17500
              ]))
              ->lsp_jl($order['ip'])
              ->ex();
            
            $form = $this->getForm($http->getBody());
            Pro::logger('order')->resid($order['orderid'])->info('获取表单',json_encode($form,256));
            if(!$form){
                Pro::logger('order')->resid($order['orderid'])->error('接口下单失败1');
                return [];
            }
            Pro::logger('order')->resid($order['orderid'])->info('接口下单成功1');
            
            if($order['pay_type'] == 'wxpay'){

                $qrcode = $this->getwx($http->getBody());
                if(!$qrcode){
                    Pro::logger('order')->resid($order['orderid'])->error('提取二维码失败');
                    return [];
                }

                return ['syorder' => $form['cardOrders.spsn'] , 'payurl' => ['url' => 'https://chong.changyou.com'.$qrcode , 'qrcode' => '' ]];
            }
            
            return $this->tradeAlipay($form,$order);

        
    }
    
    public function getwx($htmlContent) {
    
        // 使用正则表达式精准提取id="code"的img标签的src值
        // 匹配模式:
        // 1. <img id="code" - 精确匹配id为code的img标签
        // 2. src="..." - 提取src属性的值
        // 3. /qrcode/weiXinImage.do - 确保是二维码接口
        $pattern = '/<img\s+id="code"[^>]+src="([^"]+)"/';
    
        if (preg_match($pattern, $htmlContent, $matches)) {
            return $matches[1];
        }
    
        return null;
    }
    
    public function tradeAlipay($form,$order){
    $ext = userAgent();
    $http =     Pro::http()
                ->url('https://chong.changyou.com/tl/addAlipayCardOrders.do')
                ->method('POST')
                ->header('referer: https://chong.changyou.com/
                User-Agent: ' . $ext['u'])
                ->body(http_build_query($form))
                ->lsp_jl($order['ip'])
                ->ex();

            $url = $this->geturl($http->getBody(),$order);
            
            if(!$url){
                
                return [];
            }
            
            Pro::logger('order')->resid($order['orderid'])->success('获取支付链接成功');
            
            $url = 'alipays://platformapi/startapp?appId=20000067&clientVersion=3.7.0.0718&url='.urlencode($url);
            
            return ['syorder' => $form['cardOrders.spsn'] , 'payurl' => ['url' => $url , 'qrcode' => '' ]];

    
    }
    
    public function geturl($html,$order) {

        // 匹配JavaScript代码中的var url = '...';模式
        preg_match('/var\s+url\s*=\s*["\']([^"\']+)["\'];/', $html, $matches);
        
        // 如果找到匹配项，则返回URL值，否则返回空字符串
        $url = isset($matches[1]) ? $matches[1] : '';
        
        //return $url;
        if(!$url){
          Pro::logger('order')->resid($order['orderid'])->error('获取支付链接失败1',$html);
          return null;
        }

        //获取畅游支付宝数据
        $http = Pro::http()->url($url)->method('GET')->header('referer: https://chong.changyou.com/')->lsp_jl($order['ip'])->ex();
        
        
        
        
        //解析支付宝数据
        $alipayData = $this->getAlipayData($http->getBody());
        
        if (empty($alipayData) || !isset($alipayData['action'])) {
            Pro::logger('order')->resid($order['orderid'])->error('获取支付链接失败2');
            return '';
        }
        
        return $this->getRedirectUrl($alipayData['action'], 'biz_content=' . urlencode($alipayData['biz_content']) );
        
    }
    
    public function getForm(string $html): array {
        $result = [];
        
        // 定位第一个form标签及其内容
        preg_match('/<form\b[^>]*>(.*?)<\/form>/is', $html, $formMatches);
        if (empty($formMatches)) return $result;
    
        // 在form内容中提取所有input元素的name和value属性
        preg_match_all(
            '/<input\s+[^>]*?(?:\bname\s*=\s*(["\']?)([^\s"\'>]*)\1|\bvalue\s*=\s*(["\']?)([^\s"\'>]*)\3)[^>]*?(?:\bname\s*=\s*(["\']?)([^\s"\'>]*)\5|\bvalue\s*=\s*(["\']?)([^\s"\'>]*)\7)[^>]*?(?:\/)?>/i',
            $formMatches[1],
            $inputMatches,
            PREG_SET_ORDER
        );
    
        foreach ($inputMatches as $match) {
            // 获取name属性值（可能在组2或组6中）
            $name = !empty($match[2]) ? $match[2] : (!empty($match[6]) ? $match[6] : '');
            // 获取value属性值（可能在组4或组8中）
            $value = !empty($match[4]) ? $match[4] : (!empty($match[8]) ? $match[8] : '');
            
            // 只有当name存在时才添加到结果中
            if (!empty($name)) {
                $result[$name] = $value;
            }
        }
        $result['costTime'] = 5150;
        return $result;
    }
    
    public function getAlipayData(string $html) {
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
    
    public function getRedirectUrl(string $url, string $body = ''): string {
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
                    'origin: https://peak.changyou.com',
                    'content-type: application/x-www-form-urlencoded',
                    'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                    'accept-language: en-US,en;q=0.9',
                    'sec-fetch-site: cross-site',
                    'sec-fetch-mode: navigate',
                    'sec-fetch-dest: document',
                    'referer: https://peak.changyou.com/',
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
        $spsn = $order['syorder'];
        $Json = $order['account']['config'];
        $url = 'https://chong.changyou.com/tl/completePay.do?gameType='. $Json['gametype'] .'&cardOrders.gameType='. $Json['gametype'] .'&cardOrders.chnl=236&cardOrders.spsn='.$spsn.'&payWayChnlCode=235';
        $http =  Pro::http()
                 ->method('POST')
                 ->url($url)
                 ->header('Content-Type: application/x-www-form-urlencoded;charset=UTF-8
                            Origin: https://chong.changyou.com
                            Referer: https://chong.changyou.com/tl/confirmCardOrders.do
                            User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1')
                 ->formData([
                    'chnlType' => 'alipay',
                    'costTime' => 0,
                 ])
                 ->lsp_tps()
                 ->ex();
        
        if(!$http->getBody()){
            Pro::logger('order')->resid($order['orderid'])->error('回调查询失败');
            return false;
        }
        
        if(mb_strpos($http->getBody(),'恭喜您，充值成功！')){
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
            return json_encode(['code' => 0 ,'msg' => '订单不存在']);
        }
        $payResult = $this->queryOrder($order);
        
        $spsn = $order['syorder'];
        $Json = $order['account']['config'];
        $url = 'https://chong.changyou.com/tl/completePay.do?gameType='. $Json['gametype'] .'&cardOrders.gameType='. $Json['gametype'] .'&cardOrders.chnl=236&cardOrders.spsn='.$spsn.'&payWayChnlCode=235';
        $http =  Pro::http()
                 ->method('POST')
                 ->url($url)
                 ->header('Content-Type: application/x-www-form-urlencoded;charset=UTF-8
                            Origin: https://chong.changyou.com
                            Referer: https://chong.changyou.com/tl/confirmCardOrders.do
                            User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1')
                 ->formData([
                    'chnlType' => 'alipay',
                    'costTime' => 0,
                 ])
                 ->lsp_tps()
                 ->ex();
        return $http->getBody();  
        return $payResult ? '已支付' : '未支付';
        
    }
    
    public function queryTradeOrder($ck,$money,$crTimesmap)
    {

        
        
    }
    
    public function bind_callback($post)
    {
        
        
    }

}
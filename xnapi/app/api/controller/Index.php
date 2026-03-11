<?php
namespace app\api\controller;

use Webman\RedisQueue\Client;
use support\Request;
use think\facade\Db;
use app\api\traits\Pro;

class Index extends Api
{
    
    protected $noNeedLogin = ['encode'];
    
    public function __construct(Request $request = null)
    {
        parent::__construct();
        
    }
    
    public function index(Request $request)
    {
        
        return $this->success('ok3api');
        
    }
    
    public function post_ds()
    {
        
        
        
        //同步token
        $params = [
            'url' => 'https://seckillapi.phone580.com/fzsuserapi/api/int/sycnuserinfo?authToken=c838130c7ee9575551446b958b33bd84',
            //'type' => 'json'
        ];
        $http = http_get($params);
        if(!isset($http['valueObject']['authToken'])){
            //return false;
        }
        return $http;
        //下单
        $params = [
            'url'  => 'https://seckillapi.phone580.com/fzs-order-api/project/new-placeorder?authToken=2e2323efc3099f07ddcaba7ddf498727',
            //'type' => 'json',
            'header' => 'Host: seckillapi.phone580.com
Accept: application/json, text/plain, */*
Sec-Fetch-Site: same-site
Accept-Language: zh-CN,zh-Hans;q=0.9
Sec-Fetch-Mode: cors
Content-Type: application/json;charset=utf-8
Origin: https://promotion.phone580.com
User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 FZS_IOS
Referer: https://promotion.phone580.com/
Content-Length: 1188
Connection: keep-alive
Sec-Fetch-Dest: empty',
            'body' => '{"account":"uvOAb22KuUWue92ADtwanw==","nonce":"00017547316401666707093632752000","num":"1","payment":{"payMethodCode":"ALIPAY","payMethodSubCode":"ALIPAY_SDK","payReturnUrl":"https://promotion.phone580.com/activity/fzsapp-xch5-xnzf1/#/result?state=success","unpayReturnUrl":"https://promotion.phone580.com/activity/fzsapp-xch5-xnzf1/#/detail","ext":{}},"projectCode":"YXZY_XCX","orderSource":"iOS","signVersion":"1.0","skuCode":"HM_XS_100","timestamp":"1754731640000","version":"1.0","channelAccount":"","ext":{"account":"uvOAb22KuUWue92ADtwanw==","userId":70467684},"marketingParam":{"schemeNo":"ed83d6e6c6bd4f279ac2256adcbf45e8","authType":"YXZY_XCX","authToken":"2e2323efc3099f07ddcaba7ddf498727","userId":70467684,"inviteCode":""},"otherProperties":{"authType":"","authToken":"2e2323efc3099f07ddcaba7ddf498727","user_qr_code":""},"sign":"X4jAoOPHtdiWGxlm9PMS6HjQ9bI4L66r1eki2niaMInPNDhCZ9u0UZA040wLOxHqV3wt/vpNVLS5jlOOuuoM/SJe6Jbwi4PW64dGh+5Q+NyCi98yEffF4zBUmZlsHVZmR181clTfC1JJMOVJGheIeyDtbaXCzaIklJuyWcdGG9AX4AVFXgYbtEJCDs7pEzIYVI1cIWbZnyfTkjkwtEx++ndpepQc76u2y/sPiUAkOQjwHHTajxwedrPKlX4LKGB6AE4NkzSz2PFol1BL4dwqHlOJi+tTnVaU+agxk6MT7sBt/WboYp7MywGq6l2zR+BnyE64qjqoRUuQG5qC2pLdlA=="}',
        ];
        //$http = http_post($params);
        
        //return $http;
        //查单
        $params = [
            'url'  => 'https://seckillapi.phone580.com/fzs-order-api/project/new-orderlist?authToken=2e2323efc3099f07ddcaba7ddf498727&sign=627BDD8DB949CAB6F539A45ACBCDB9B2E3321B73&appKey=1&timestamp=20250809171813',
            //'type' => 'json',
            'header' => 'Host: seckillapi.phone580.com
Accept: application/json, text/plain, */*
Sec-Fetch-Site: same-site
Accept-Language: zh-CN,zh-Hans;q=0.9
Sec-Fetch-Mode: cors
Content-Type: application/json;charset=utf-8
Origin: https://promotion.phone580.com
User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 FZS_IOS
Referer: https://promotion.phone580.com/
Content-Length: 173
Connection: keep-alive
Sec-Fetch-Dest: empty',
            'body' => '{"projectCode":"YXZY_XCX","number":30,"page":1,"startTime":"2025-08-07","endTime":"2025-08-09","statusCodes":[],"marketingExt":{},"productType":"ALL","orderBusinessType":""}',
        ];
        $http = http_post($params);
        
        return $http;
        
    }
    
    public function post_d()
    {
            
        $r = Pro::http()->url('https://pay.aligames.com/pay.htm?token=03a05d565c05c4f03a7a3def106615e72')
            ->method('301')
            ->header('host: pay.aligames.com
                    sec-ch-ua: "Not)A;Brand";v="24", "Chromium";v="116"
                    sec-ch-ua-mobile: ?1
                    sec-ch-ua-platform: "Android"
                    upgrade-insecure-requests: 1
                    dnt: 1
                    user-agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Mobile Safari/537.36
                    accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
                    sec-fetch-site: cross-site
                    sec-fetch-mode: navigate
                    sec-fetch-user: ?1
                    sec-fetch-dest: document
                    referer: https://m.jiaoyimao.com/
                    accept-language: zh-CN,zh;q=0.9')
            ->ex(true)
            ->getBody();
        return $r;
    }

    public function encode(Request $request)
    {
        
        $ip2region = new \os\iplib\Ip2Region;

        $info = $ip2region->btreeSearch($request->header('x-real-ip'));
        $result = [
            'ip' => $request->header('x-real-ip'),
            'info' => $info,
            'post' => $request->post(),
            'header' =>  $request->header()
        ];
        return json($result);
        
        
        return;
        $qrcode = new \Zxing\QrReader('1742731008325.jpg');
        $text = $qrcode->text();
        print_r($text);
    }
    
    public function ipinfo(Request $request)
    {
        
        $ip2region = new \os\iplib\Ip2Region;
        $ip = $request->get('ip',0);

        $info = $ip2region->btreeSearch($ip);
        $result = [
            'ip' => $ip,
            'info' => $info,
            'post' => $request->post(),
            'header' =>  $request->header()
        ];
        return json($result);
        
    }
    
    public function fun()
    {
        
        $base = \config('backend.api_base_url', '');
        $url = $base === '' || $base === null ? '' : rtrim($base, '/') . '/api/index/encode';
        if ($url === '') {
            return $this->error('未配置 api_base_url');
        }
        $response = Pro::http()
                ->method('GET')
                ->url($url)
                ->lsp()
                ->ex();
        
        return $response->getBody();
        
    }
    
    public function red()
    {
        
        return view('index/red');
        
        $response = Pro::http()
                ->method('GET')
                ->url('https://openapi.alipay.com/gateway.do?charset=UTF-8&biz_content=%7B%22out_trade_no%22%3A%22110000000363dbb774d32ce3562a9%22%2C%22total_amount%22%3A%220.10%22%2C%22quit_url%22%3A%22https%3A%2F%2Fwww.xiaohongshu.com%2F%22%2C%22subject%22%3A%22%E5%B0%8F%E7%BA%A2%E4%B9%A6%E8%AE%A2%E5%8D%95%3A+137563654343218248%22%2C%22business_params%22%3A%7B%22outTradeRiskInfo%22%3A%22%7B%5C%22mcCreateTradeTime%5C%22%3A%5C%222025-09-04+18%3A30%3A07%5C%22%7D%22%7D%2C%22timeout_express%22%3A%22120m%22%2C%22passback_params%22%3A%22%7B%5C%22subject_id%5C%22%3A%5C%22BA7CB52250B0D1B6BEBFA6E9037E079D47380B71A2B434D0DF7962F95808EDDF%5C%22%7D%22%2C%22product_code%22%3A%22QUICK_WAP_WAY%22%2C%22enable_pay_channels%22%3A%22balance%2CmoneyFund%2CbankPay%2CdebitCardExpress%22%7D&method=alipay.trade.wap.pay&sign=DtU%2FXc0TAeerKHDsUSG8Bu0f8WSRMs6VJqJMEjfs2e0P%2FTMHL2zeaTKHrUc%2Fb6o4gCYkpAcPCLGPIFqJvh6uknnUMVFDFFZnu04M93CMzl5jfpiR6esAG%2Fu0Lkh5UFh%2BCDPtSfOrJCkGA89aOLrsA5ZObRrG51wJql9PKbuWYuJ8cQ7J04%2BAwnrcmv3SFd%2BMiVKR0%2FXzxj8COffPi4D9SVYZct1IPZ9flSf4hgOzLTPZBYZ%2BFpYGNvyKCITJZNED6ZF2CYCgl%2F0rTLXpXgiZ6jIAe62EYFp8ovKc7xHaLd3yLXtx0fkE9hlXLX30GDPdZB9euxtfpjlaZEJRJgxJWA%3D%3D&notify_url=https%3A%2F%2Fmall.xiaohongshu.com%2Fpayment%2Falipay_sub%2Fnotify&app_id=2019062165637671&sign_type=RSA2&version=1.0&timestamp=2025-09-04+18%3A30%3A07')
                ->ex();
        
        $result = $response->getBody();
        $result .= '</script>';
        
        return $result;
        
    }
    
    public function red_cookie()
    {
        $html = Pro::http()
                ->url('https://mclient.alipay.com/cashier/mobilepay.htm?alipay_exterface_invoke_assign_target=invoke_fb0f65668b1a6ef3302f7335568d3248&alipay_exterface_invoke_assign_sign=b7c96sa%2Bb0_to%2Fa_tq_p1n7a_r4_z4_d8_ttpusk_k_t_a_nyee_hu_b8khwx_f_p_l_gxg%3D%3D')
                ->ex();
                
        $str = getMetaContent($html->getBody());
        
        $querystr = queryStr($str);
        $querystr['query_params'] = queryStr($querystr['query_params']);
        
        $payurl = 'https://mclient.alipay.com/h5pay/h5RouteAppSenior/index.html?server_param='. $querystr['serverParams'] .'&contextId='.$querystr['query_params']['awid'].'&cookieToken='. $querystr['cookieToken'] .'&pageToken=&refreshNoAuth=Y';
        
        return $payurl;
        
        $html = Pro::http()
                ->url('https://mclient.alipay.com/cashier/mobilepay.htm?alipay_exterface_invoke_assign_target=invoke_6b06deef2544e9355f21c6775d1d6f5d&alipay_exterface_invoke_assign_sign=_is_d_iv_i0l_n1_ct_d_k0_pie_wxi7_ume_a_vhq_i%2Bb_f%2F6_o%2B33oy%2F_e4_w_t_o_l%2Ba8%2Fw_a%3D%3D')
                ->ex();
        
        
        return getMetaContent($html->getBody());
        
    }
    
    public function get_os(Request $request){
        
        
        //return json($request->header('user-agent'));
        return Pro::ip_city();
        
        return detectDevice($request->header('user-agent'));
        
    }
    
    public function sss(Request $request){
        
        
        $ip = Pro::ip();
        //return json($request->header());
        $base = \config('backend.api_base_url', '');
        $url = $base === '' || $base === null ? '' : rtrim($base, '/') . '/api/index/encode';
        if ($url === '') {
            return json(['code' => 0, 'msg' => '未配置 api_base_url']);
        }
        $html = Pro::http()
                ->method("GET")
                ->url($url)
                ->header('
                user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Safari/537.36 SE 2.X MetaSr 1.0
                ')
                ->lsp_jl('175.153.163.82')
                ->ex();
        return $html->getBody();
        return json($html->getInfo());
        
        $geo     = '';
        $ip_city = '';
        if($ip){
            $geo = (new \os\iplib\Ip2Region)->btreeSearch($ip);
            
            if (strpos($geo, '中国') !== false && strpos($geo, '香港') == false  && strpos($geo, '台湾') == false && strpos($geo, '澳门') == false ) {
                $cityinfo = explode('|',$geo);
                
                $ip_city = $cityinfo[2] != 0 ? $cityinfo[2] : $cityinfo[3];
                if($ip_city == 0){
                    $ip_city = '深圳';
                }

            }else{
                $ip_city = '深圳';

            }
            
            
        }
        
        return $ip_city;
        
    }
    

}
<?php
namespace app\api\module;

use support\Request;
use app\api\traits\Pro;
use think\facade\Db;
use Bilulanlv\ThinkCache\facade\ThinkCache;

class Bilibili
{

    public $access_id   = 37;
    public $query_delay = 5;
    
    public function build($order,$account)
    {
        $accountData = $this->match_city($order,$account);
        if($accountData){
           $account = $accountData;
        }
        
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
    
    public function match_city($order,$account)
    {
        if(!$order['ip']){
            return null;
        }
        $geo = (new \os\iplib\Ip2Region)->btreeSearch($order['ip']);
        $cityinfo = explode('|',$geo);
        $ip_city = $cityinfo[1];
        
        if($ip_city == 0){
            return null;
        }
        
        $fee  =  $order['fee'];
        $data = Pro::model('Account')
                   ->where([
                   'user_id' => $account['user_id'],
                   'switch'  => 1
                   ])
                   ->where('value2','like',"%$ip_city%")
                   ->where(function ($query) use ($fee) {
                        $query->where('limit_min_fee', '<=', $fee)
                              ->whereOr('limit_min_fee', '=', 0);
                    })
                    ->where(function ($query) use ($fee) {
                        $query->where('limit_max_fee', '>=', $fee)
                              ->whereOr('limit_max_fee', '=', 0);
                    })
                    ->where(function ($query) use ($fee) {
                        $query->where('limit_fee', '>=', $fee)
                              ->whereOr('limit_fee', '=', 0);
                    })
                   ->order('match_time')
                   ->order('active_time desc')
                   ->find();
        
        if(!$data){
            return null;
        }
        
        $account->match_time = time();
        $account->save();
        
        return $data;
    }
    
    public function get_pvid($ck) {
        $key = sha1($ck);
        $cache = ThinkCache::get(sha1($ck));
        if($cache){
            //print_r('1:'.$cache);
            return $cache;
        }
        $ckinfo = $this->getCookieValue($ck);
        if(!isset($ckinfo['PVID'])){
            $pvid = 1;
        }else{
            $pvid = $ckinfo['PVID'];
        }

        ThinkCache::set($key,$pvid,3600);
        
    }
    
    public function e_log_pv($ck,$order,$type,$new_order_id = null) {

        
        $pvid = $this->get_pvid($ck);
        $timestamp = round(microtime(true) * 1000);
        if($type == 1){
            $query = '0000'. $timestamp .'https://link.bilibili.com/p/live-h5-recharge/index.html|444.65.selfDef.chargemoney_click||'. $timestamp .'|||400x850|1|{"show_type":"-99998","tcode":"-99998","openid":"-99998"}||'. $pvid ;
        }
        
        if($type == 2){
            $query = '0000'.$timestamp.'https://link.bilibili.com/p/live-h5-recharge/index.html|444.65.selfDef.chargesubmit_click||'.$timestamp.'|||400x850|2|{"show_type":"-99998","tcode":"-99998","openid":"-99998","goods_id":"1","pay_method":"1","hamster_to_batttery":"3"}||'.$pvid;
        }
        
        if($type == 3){
            $query = '0000'. $timestamp .'https://link.bilibili.com/p/live-h5-recharge/index.html|444.67.guard-pay-success.0.show||'. $timestamp .'|||400x850|2|{"new_order_id":"'. $new_order_id .'"}||'.$pvid;
        }
        if($type == 4){
            $query = 'content_type=pbrequest&logid=021436&disable_compression=true';
        }
        
        $query = urlencode($query);
        $http = Pro::http()
            ->url('https://data.bilibili.com/log/web?'.$query)
            ->method('GET')
            ->header('Accept:application/json, text/plain, */*
                Content-Type:application/x-www-form-urlencoded
                Cookie:'. $ck .'
                Dnt:1
                Origin:https://live.bilibili.com/
                Referer:https://live.bilibili.com/
                Sec-Ch-Ua:"Not)A;Brand";v="24", "Chromium";v="116"
                Sec-Ch-Ua-Mobile:?0
                Sec-Ch-Ua-Platform:"iPhone"
                Sec-Fetch-Dest:empty
                Sec-Fetch-Mode:cors
                Sec-Fetch-Site:same-site
                User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1
                ')
            ->lsp_jl($order['ip'])
            ->ex();
            
        Pro::logger('order')->resid($order['orderid'])->info('过人机风控点击-'. $type );
        
    }
    
    public function e_log_pv_a($ck,$order,$url) {

        $pvid = $this->get_pvid($ck);
        
        $ckinfo = $this->getCookieValue($ck);
        $timestamp = round(microtime(true) * 1000);
        $query = '0000141767505493674'. urlencode($url) .'|https://link.bilibili.com/||'. $timestamp .'||400x850|2|{"b_nut_h":'. $ckinfo['b_nut'] .',"lsid":"'.$ckinfo['b_lsid'].'","buvid_fp":"'. $ckinfo['buvid_fp'] .'","buvid4":"'. $ckinfo['buvid4'] .'","bsource_origin":"empty","share_source_origin":"empty"}|{}|'. $ckinfo['buvid4'] .'|zh-CN|null|undefined';
        $query = urlencode($query);
        
        $http = Pro::http()
            ->url('https://data.bilibili.com/log/web?'.$query)
            ->method('POST')
            ->header('Accept:application/json, text/plain, */*
                Content-Type:application/x-www-form-urlencoded
                Cookie:'. $ck .'
                Dnt:1
                Origin:https://live.bilibili.com/
                Referer:https://live.bilibili.com/
                Sec-Ch-Ua:"Not)A;Brand";v="24", "Chromium";v="116"
                Sec-Ch-Ua-Mobile:?0
                Sec-Ch-Ua-Platform:"iPhone"
                Sec-Fetch-Dest:empty
                Sec-Fetch-Mode:cors
                Sec-Fetch-Site:same-site
                User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1
                ')
            ->lsp_jl($order['ip'])
            ->ex();
            Pro::logger('order')->resid($order['orderid'])->info('过人机风控订单');
    }
    
    public function ExClimbWuzhi($order,$account,$ext)
    {
        $ckinfo = $this->getCookieValue($account['ck']);
        $body = '{"payload":"{\"3064\":2,\"5062\":\"1768407901468\",\"03bf\":\"https%3A%2F%2Fpay.bilibili.com%2Fpayplatform-h5%2Fcashierdesk.html\",\"39c8\":\".fp.risk\",\"34f1\":\"\",\"d402\":\"\",\"654a\":\"\",\"6e7c\":\"400x843\",\"3c43\":{\"2673\":0,\"5766\":24,\"6527\":0,\"7003\":1,\"807e\":1,\"b8ce\":\"Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Mobile Safari/537.36\",\"641c\":0,\"07a4\":\"zh-CN\",\"1c57\":8,\"0bd0\":16,\"748e\":[843,400],\"d61f\":[843,400],\"fc9d\":-480,\"6aa9\":\"Asia/Shanghai\",\"75b8\":1,\"3b21\":1,\"8a1c\":1,\"d52f\":\"not available\",\"adca\":\"Win32\",\"80c9\":[],\"13ab\":\"UjIAAAAASUVORK5CYII=\",\"bfe9\":\"0kAxTIRgGAlY3VJIoC9hX4P9gqCrUrs+9RAAAAAElFTkSuQmCC\",\"a3c1\":[\"extensions:ANGLE_instanced_arrays;EXT_blend_minmax;EXT_color_buffer_half_float;EXT_disjoint_timer_query;EXT_float_blend;EXT_frag_depth;EXT_shader_texture_lod;EXT_texture_compression_bptc;EXT_texture_compression_rgtc;EXT_texture_filter_anisotropic;EXT_sRGB;KHR_parallel_shader_compile;OES_element_index_uint;OES_fbo_render_mipmap;OES_standard_derivatives;OES_texture_float;OES_texture_float_linear;OES_texture_half_float;OES_texture_half_float_linear;OES_vertex_array_object;WEBGL_color_buffer_float;WEBGL_compressed_texture_s3tc;WEBGL_compressed_texture_s3tc_srgb;WEBGL_debug_renderer_info;WEBGL_debug_shaders;WEBGL_depth_texture;WEBGL_draw_buffers;WEBGL_lose_context;WEBGL_multi_draw\",\"webgl aliased line width range:[1, 1]\",\"webgl aliased point size range:[1, 1024]\",\"webgl alpha bits:8\",\"webgl antialiasing:yes\",\"webgl blue bits:8\",\"webgl depth bits:24\",\"webgl green bits:8\",\"webgl max anisotropy:16\",\"webgl max combined texture image units:32\",\"webgl max cube map texture size:16384\",\"webgl max fragment uniform vectors:1024\",\"webgl max render buffer size:16384\",\"webgl max texture image units:16\",\"webgl max texture size:16384\",\"webgl max varying vectors:30\",\"webgl max vertex attribs:16\",\"webgl max vertex texture image units:16\",\"webgl max vertex uniform vectors:4096\",\"webgl max viewport dims:[32767, 32767]\",\"webgl red bits:8\",\"webgl renderer:WebKit WebGL\",\"webgl shading language version:WebGL GLSL ES 1.0 (OpenGL ES GLSL ES 1.0 Chromium)\",\"webgl stencil bits:0\",\"webgl vendor:WebKit\",\"webgl version:WebGL 1.0 (OpenGL ES 2.0 Chromium)\",\"webgl unmasked vendor:Google Inc. (AMD)\",\"webgl unmasked renderer:ANGLE (AMD, Radeon RX 570 Series Direct3D11 vs_5_0 ps_5_0, D3D11)\",\"webgl vertex shader high float precision:23\",\"webgl vertex shader high float precision rangeMin:127\",\"webgl vertex shader high float precision rangeMax:127\",\"webgl vertex shader medium float precision:23\",\"webgl vertex shader medium float precision rangeMin:127\",\"webgl vertex shader medium float precision rangeMax:127\",\"webgl vertex shader low float precision:23\",\"webgl vertex shader low float precision rangeMin:127\",\"webgl vertex shader low float precision rangeMax:127\",\"webgl fragment shader high float precision:23\",\"webgl fragment shader high float precision rangeMin:127\",\"webgl fragment shader high float precision rangeMax:127\",\"webgl fragment shader medium float precision:23\",\"webgl fragment shader medium float precision rangeMin:127\",\"webgl fragment shader medium float precision rangeMax:127\",\"webgl fragment shader low float precision:23\",\"webgl fragment shader low float precision rangeMin:127\",\"webgl fragment shader low float precision rangeMax:127\",\"webgl vertex shader high int precision:0\",\"webgl vertex shader high int precision rangeMin:31\",\"webgl vertex shader high int precision rangeMax:30\",\"webgl vertex shader medium int precision:0\",\"webgl vertex shader medium int precision rangeMin:31\",\"webgl vertex shader medium int precision rangeMax:30\",\"webgl vertex shader low int precision:0\",\"webgl vertex shader low int precision rangeMin:31\",\"webgl vertex shader low int precision rangeMax:30\",\"webgl fragment shader high int precision:0\",\"webgl fragment shader high int precision rangeMin:31\",\"webgl fragment shader high int precision rangeMax:30\",\"webgl fragment shader medium int precision:0\",\"webgl fragment shader medium int precision rangeMin:31\",\"webgl fragment shader medium int precision rangeMax:30\",\"webgl fragment shader low int precision:0\",\"webgl fragment shader low int precision rangeMin:31\",\"webgl fragment shader low int precision rangeMax:30\"],\"6bc5\":\"Google Inc. (AMD)~ANGLE (AMD, Radeon RX 570 Series Direct3D11 vs_5_0 ps_5_0, D3D11)\",\"ed31\":0,\"72bd\":1,\"097b\":0,\"52cd\":[1,1,1],\"a658\":[\"Arial\",\"Arial Black\",\"Arial Narrow\",\"Calibri\",\"Cambria\",\"Cambria Math\",\"Comic Sans MS\",\"Consolas\",\"Courier\",\"Courier New\",\"Georgia\",\"Helvetica\",\"Impact\",\"Lucida Console\",\"Lucida Sans Unicode\",\"Microsoft Sans Serif\",\"MS Gothic\",\"MS PGothic\",\"MS Sans Serif\",\"MS Serif\",\"Palatino Linotype\",\"Segoe Print\",\"Segoe Script\",\"Segoe UI\",\"Segoe UI Light\",\"Segoe UI Semibold\",\"Segoe UI Symbol\",\"Tahoma\",\"Times\",\"Times New Roman\",\"Trebuchet MS\",\"Verdana\",\"Wingdings\"],\"d02f\":\"124.04347527516074\"},\"54ef\":\"{}\",\"8b94\":\"https%3A%2F%2Flink.bilibili.com%2F\",\"df35\":\"A2DE6973-D4CA-B2E5-2D67-5109D2BC6D8C477679infoc\",\"07a4\":\"zh-CN\",\"5f45\":null,\"db46\":0}"}';
        
        $http = Pro::http()
            ->url('https://api.live.bilibili.com/xlive/revenue/v1/order/createCashOrder')
            ->method('POST')
            ->header('Accept:application/json, text/plain, */*
                Content-Type:application/x-www-form-urlencoded
                Cookie:'. $account['ck'] .'
                Dnt:1
                Origin:https://pay.bilibili.com
                Referer:https://pay.bilibili.com/
                Sec-Ch-Ua:"Not)A;Brand";v="24", "Chromium";v="'. $ext['v'] .'"
                Sec-Ch-Ua-Mobile:?0
                Sec-Ch-Ua-Platform:"iPhone"
                Sec-Fetch-Dest:empty
                Sec-Fetch-Mode:cors
                Sec-Fetch-Site:same-site
                User-Agent: ' . $ext['u'])
            ->lsp_jl($order['ip'])
            ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('搜集信息');
        
    }
    
    public function payplatform($order,$account,$url,$ext)
    {
        $http = Pro::http()
            ->url($url)
            ->method('GET')
            ->header('Accept:application/json, text/plain, */*
                Content-Type:application/x-www-form-urlencoded
                Cookie:'. $account['ck'] .'
                Dnt:1
                Origin:https://pay.bilibili.com
                Referer:https://pay.bilibili.com/
                Sec-Ch-Ua:"Not)A;Brand";v="24", "Chromium";v="'. $ext['v'] .'"
                Sec-Ch-Ua-Mobile:?0
                Sec-Ch-Ua-Platform:"iPhone"
                Sec-Fetch-Dest:empty
                Sec-Fetch-Mode:cors
                Sec-Fetch-Site:same-site
                User-Agent: ' . $ext['u'])
            ->lsp_jl($order['ip'])
            ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('打开页面');
        
    }
    
    public function report($order,$account,$step,$traceid,$ext,$value = '')
    {
        
        if($step == 1){
            $log = urlencode('[{"level":"info","logtype":1,"url":"https://api.bilibili.com/x/web-interface/nav","status":200,"cost":209,"traceid_end":"'. $traceid .'","traceid_svr":null,"sub_product":null}]');
        }elseif ($step == 2) {
            $log = urlencode('[{"level":"info","logtype":1,"url":"https://pay.bilibili.com/payplatform/pay/getPayChannel","status":200,"cost":99,"traceid_end":"'. $traceid .'","traceid_svr":null,"sub_product":null}]');
        }elseif ($step == 3) {
            $log = urlencode('[{"level":"error","message":"Script error.","srcUrl":"'. $value .'","traceid_end":"'. $traceid .'","sub_product":null}]');
        }elseif ($step == 4) {
            $log = urlencode('[{"level":"info","logtype":2,"url":"https://pay.bilibili.com/payplatform-h5/cashierdesk.html","navigationStart":0,"redirectStart":0,"redirectEnd":0,"fetchStart":2,"domainLookupStart":2,"domainLookupEnd":2,"connectStart":2,"secureConnectionStart":0,"connectEnd":2,"requestStart":236,"responseStart":295,"responseEnd":296,"domLoading":305,"domInteractive":1849,"domContentLoadedEventStart":1849,"domContentLoadedEventEnd":1849,"domComplete":1851,"loadEventStart":1852,"loadEventEnd":1852,"firstPaint":632,"firstContentfulPaint":1867,"sub_product":null}]');
        }elseif ($step == 5) {
            $log = urlencode('[{"level":"info","logtype":1,"url":"https://cn-fp.apitd.net/web/v2","cost":330,"sub_product":null},{"level":"info","logtype":1,"url":"https://api.bilibili.com/x/web-interface/nav","cost":207,"sub_product":null},{"level":"info","logtype":1,"url":"https://pay.bilibili.com/payplatform/pay/getPayChannel","cost":98,"sub_product":null}]');
        }elseif ($step == 6) {
            $log = urlencode('[{"level":"info","logtype":1,"url":"https://pay.bilibili.com/payplatform/pay/pay","status":200,"cost":174,"traceid_end":"'. $traceid .'","traceid_svr":null,"sub_product":null}]');
        }
        
        
        
        $http = Pro::http()
            ->url( 'https://api.bilibili.com/open/monitor/report?source=payment&log=' . $log)
            ->method('GET')
            ->header('Accept:application/json, text/plain, */*
                Content-Type:application/x-www-form-urlencoded
                Cookie:'. $account['ck'] .'
                Dnt:1
                Origin:https://pay.bilibili.com
                Referer:https://pay.bilibili.com/
                Sec-Ch-Ua:"Not)A;Brand";v="24", "Chromium";v="'. $ext['v'] .'"
                Sec-Ch-Ua-Mobile:?0
                Sec-Ch-Ua-Platform:"iPhone"
                Sec-Fetch-Dest:empty
                Sec-Fetch-Mode:cors
                Sec-Fetch-Site:same-site
                User-Agent: ' . $ext['u'])
            ->lsp_jl($order['ip'])
            ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('上报步骤-'. $step .'响应结果'. $http->getBody() );
        
    }
    
    public function generatetraceid($length = 23)
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= random_int(0, 9);
        }
        return $result;
    }
    
    public function generateCustomUUID()
    {
        $parts = [
            bin2hex(random_bytes(4)),  // 8字符
            bin2hex(random_bytes(2)),  // 4字符
            bin2hex(random_bytes(2)),  // 4字符
            bin2hex(random_bytes(2)),  // 4字符
            bin2hex(random_bytes(6))   // 12字符
        ];
        
        return implode('-', $parts);
    }
    
    public function toweb($order,$account,$ext)
    {
        
        $http = Pro::http()
            ->url( 'https://api.bilibili.com/x/web-interface/nav')
            ->method('GET')
            ->header('Accept:application/json, text/plain, */*
                Content-Type:application/x-www-form-urlencoded
                Cookie:'. $account['ck'] .'
                Dnt:1
                Origin:https://pay.bilibili.com
                Referer:https://pay.bilibili.com/
                Sec-Ch-Ua:"Not)A;Brand";v="24", "Chromium";v="'. $ext['v'] .'"
                Sec-Ch-Ua-Mobile:?0
                Sec-Ch-Ua-Platform:"iPhone"
                Sec-Fetch-Dest:empty
                Sec-Fetch-Mode:cors
                Sec-Fetch-Site:same-site
                User-Agent: ' . $ext['u'])
            ->lsp_jl($order['ip'])
            ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('同步平台-响应结果',  $http->getBody() );
    }
    

    
    public function queryChannelList($order,$account,$parms)
    {

        $http = Pro::http()
            ->url( 'https://pay.bilibili.com/payplatform/pay/queryChannelList')
            ->method('POST')
            ->header('sec-ch-ua: "Not)A;Brand";v="24", "Chromium";v="116"
            accept: application/json, text/plain, */*
            content-type: application/json
            sec-ch-ua-mobile: ?0
            user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Safari/537.36 SE 2.X MetaSr 1.0
            sec-ch-ua-platform: "Windows"
            origin: https://pay.bilibili.com
            sec-fetch-site: same-origin
            sec-fetch-mode: cors
            sec-fetch-dest: empty
            referer: https://pay.bilibili.com/pay-v2/cashier/cashier-desk
            accept-language: zh-CN,zh;q=0.9
            Cookie:'. $account['ck'])
            ->body('{
              "uid": '. $parms['uid'] .',
              "orderId": "'. $parms['orderId'] .'",
              "serviceType": '. $parms['serviceType'] .',
              "orderCreateTime": "'. $parms['orderCreateTime'] .'",
              "orderExpire": '. $parms['orderExpire'] .',
              "feeType": "'. $parms['feeType'] .'",
              "payAmount": '. $parms['payAmount'] .',
              "originalAmount": '. $parms['originalAmount'] .',
              "deviceType": '. $parms['deviceType'] .',
              "notifyUrl": "'. $parms['notifyUrl'] .'",
              "productId": "'. $parms['productId'] .'",
              "showTitle": "'. $parms['showTitle'] .'",
              "returnUrl": "'. $parms['returnUrl'] .'",
              "traceId": "'. $parms['traceId'] .'",
              "timestamp": "'. $parms['timestamp'] .'",
              "defaultChoose": "",
              "createUa": "",
              "showContent": "",
              "productUrl": "",
              "deviceInfo": "",
              "version": "'. $parms['version'] .'",
              "customerId": '. $parms['customerId'] .',
              "signType": "'. $parms['signType'] .'",
              "extParams": "",
              "planId": 0,
              "displayAccount": "",
              "signUrl": "",
              "sign": "'. $parms['sign'] .'",
              "subscribeType": 0,
              "sdkVersion": "1.5.6"
            }')
            //->lsp_jl($order['ip'])
            ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('queryChannelList-响应结果',$http->getBody() );
    }
    
    public function tradeOrder($order,$account)
    {
        //选中金额
        //$this->e_log_pv($account['ck'],$order,1);
        //$this->e_log_pv($account['ck'],$order,1);
        //充值点击
        //$this->e_log_pv($account['ck'],$order,2);
        
        
        
        
        $ckinfo = $this->getCookieValue($account['ck']);
        $fee    = $order['fee'] * 1000;
        $ext = userAgent();
        
        //$this->ExClimbWuzhi($order,$account,$ext);
        
        //$this->opindex($order,$account,$ext);
        //$this->toweb($order,$account,$ext);
        //$this->webContent($order,$account,$ext);
        
        //$traceid = $this->generatetraceid();
        
        //$this->report($order,$account,1,$traceid,$ext);
        //$this->report($order,$account,2,$traceid,$ext);
       // $this->report($order,$account,3, $this->generateCustomUUID() ,$ext);
       // $this->report($order,$account,4,$traceid,$ext);
        //$this->report($order,$account,5,$traceid,$ext);
        
        $http = Pro::http()
            ->url('https://api.live.bilibili.com/xlive/revenue/v1/order/createCashOrder')
            ->method('POST')
            ->header('
                sec-ch-ua: "Not)A;Brand";v="24", "Chromium";v="116"
                accept: application/json, text/plain, */*
                content-type: application/x-www-form-urlencoded
                sec-ch-ua-mobile: ?0
                user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Safari/537.36 SE 2.X MetaSr 1.0
                sec-ch-ua-platform: "Windows"
                origin: https://link.bilibili.com
                sec-fetch-site: same-site
                sec-fetch-mode: cors
                sec-fetch-dest: empty
                referer: https://link.bilibili.com/
                accept-language: zh-CN,zh;q=0.9
                Cookie:'. $account['ck'] )
            ->formData([
                'context_id'   =>  0,
                'context_type' => 14,
                'pay_cash'     => $fee,
                'goods_id'     => 1,
                'goods_type'   => 2,
                'goods_num'    => (int)$order['fee'],
                'build'        => 0,
                'mobile_app'   => 'android',
                'biz_extra'    => '',
                'ruid'         => '',
                'parent_area_id' => '',
                'area_id'       => '',
                'is_contract'   => 0,
                'return_url'    => 'https://link.bilibili.com/p/live-h5-recharge/index.html',//'https://uyun89.top/submit.php/index/success', //https://link.bilibili.com/p/live-h5-recharge/index.html
                'platform'      => 'h5',
                'pay_hamster'   => 0,
                'hamster_account_type' => 0,
                'hamster_tax'   => 0,
                'hamster_rate'  => 0,
                'hamster_extra_gold' => 0,
                'live_statistics' => '{"pc_client":"pink","jumpfrom":"-99998"}',
                'statistics' => '{"platform":3,"pc_client":"pink"}',
                'csrf_token' => $ckinfo['bili_jct'], 
                'csrf'       => $ckinfo['bili_jct'],
                'visit_id'   => ''
            ])
            //->useIPv6()
            //->lsp_jl($order['ip'])  //
            ->lsp_jl('59.35.190.208') //$order['ip']
            ->ex();//219.151.28.68
            
            Pro::logger('order')->resid($order['orderid'])->info('接口下单结果',$http->getBody());

            if(!$http->getBody()){
                return [];
            }
            $data = $http->getJson();
            if(isset($data['code'])){
                if($data['code'] == '-101'){
                    Pro::model('Account')->offline($account['id'],$data['message']);
                    return [];
                }
                if($data['code'] == '1300080'){
                    Pro::model('Account')->offline($account['id'],$data['message']);
                    return [];
                }
            }

            if(!isset($data['data']['order_id'])){
                
                return [];
            }
            
            Pro::model('Account')->online($account['id']);
            
            //下单成功
            /*$this->e_log_pv($account['ck'],$order,3,$data['data']['order_id']);
            $this->e_log_pv($account['ck'],$order,3,$data['data']['order_id']);
            $this->e_log_pv($account['ck'],$order,4);*/
            
            $urljson = json_encode($data['data']['pay_center_params'],256);
            //$this->e_log_pv_a($account['ck'],$order,'https://pay.bilibili.com/payplatform-h5/cashierdesk.html?params='.urlencode($urljson));
            
            $url = 'https://pay.bilibili.com/payplatform-h5/cashierdesk.html?params='.urlencode($urljson);
            Pro::logger('order')->resid($order['orderid'])->info('接口', $url );
            
            
            //$this->report($order,$account,6,$traceid,$ext,$url);
            
            //$this->payplatform($order,$account,$url,$ext);
            
        for ($i = 0; $i < 3; $i++) {
            $payurl = $this->get_pay_url($data['data']['pay_center_params'],$order['pay_type'],$order,$account,$ext);
            if($payurl) break;
        }
        
        if(!$payurl){
            return [];
        }
        
        if($order['pay_type'] == 'alipay'){
            //$payurl = 'alipays://platformapi/startapp?appId=20000067&clientVersion=3.7.0.0718&url='.urlencode($payurl);
        }
        
        return ['syorder' => $data['data']['order_id'] , 'payurl' => ['url' => $payurl, 'qrcode' => '' ]];
        
    }
    
    public function get_pay_url($parms,$type,$order,$account,$ext) {
        
        $this->queryChannelList($order,$account,$parms);
        
        $userAgent = userAgent();
        $http = Pro::http()
                ->url('https://pay.bilibili.com/payplatform/pay/pay')
                ->method('POST')
                ->header('
                sec-ch-ua: "Not)A;Brand";v="24", "Chromium";v="116"
                accept: application/json, text/plain, */*
                content-type: application/json
                sec-ch-ua-mobile: ?0
                user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.5845.97 Safari/537.36 SE 2.X MetaSr 1.0
                sec-ch-ua-platform: "Windows"
                origin: https://pay.bilibili.com
                sec-fetch-site: same-origin
                sec-fetch-mode: cors
                sec-fetch-dest: empty
                referer: https://pay.bilibili.com/pay-v2/cashier/cashier-desk
                accept-language: zh-CN,zh;q=0.9
                Cookie:'. $account['ck'])
                ->body(json_encode([
                    "uid"             => $parms['uid'],
                    "orderId"         => $parms['orderId'], 
                    "serviceType"     => $parms['serviceType'],
                    "orderCreateTime" => $parms['orderCreateTime'],
                    "orderExpire"     => $parms['orderExpire'],
                    "feeType"         => $parms['feeType'],
                    "payAmount"       => $parms['payAmount'],
                    "originalAmount"  => $parms['originalAmount'],
                    "deviceType"      => $parms['deviceType'],
                    "notifyUrl"       => $parms['notifyUrl'],
                    "productId"       => $parms['productId'],
                    "showTitle"       => $parms['showTitle'],
                    "returnUrl"       => $parms['returnUrl'],
                    "traceId"         =>  $parms['traceId'],
                    "timestamp"       => $parms['timestamp'],
                    "defaultChoose"   => "",
                    "createUa"        => "",
                    "showContent"     => "",
                    "productUrl"      => "",
                    "deviceInfo"      => "",
                    "version"         => $parms['version'],
                    "customerId"      => $parms['customerId'],
                    "signType"        => $parms['signType'],
                    "extParams"       => "",
                    "planId"          => $parms['planId'],
                    "displayAccount"  => "",
                    "signUrl"         => "",
                    "sign"            => $parms['sign'],
                    "subscribeType"   => $parms['subscribeType'],
                    "sdkVersion"      => "1.4.8",
                    "realChannel"     => $type == 'alipay' ? 'open_alipay' : 'wechat',
                    "payChannel"      => $type == 'alipay' ? 'alipay' : 'wechat',
                    "payChannelId"    => $type == 'alipay' ? 10042 : 10003,
                ]))
                //->lsp_jl($order['ip'])
                ->lsp_jl('59.35.190.208')
                //->lsp_tps()
                ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('获取支付链接结果',$http->getBody());
        if(!$http->getBody()){
            Pro::logger('order')->resid($order['orderid'])->error('获取支付链接失败-网络错误');
            return null;
        }
        $data = $http->getJson();
        
        if(isset($data['errno'])){
           if($data['errno'] == '8008001000'){
               Pro::model('Account')->offline($account['id'],$data['showMsg']);
           }
        }
        
        if(!isset($data['data']['payChannelUrl']) ){
            Pro::logger('order')->resid($order['orderid'])->error('获取支付链接失败-数据错误');
            return null;
        }
        if($type == 'wxpay'){
            
            return $this->getwxurl($data['data']['payChannelUrl'],$order);
        }
        Pro::logger('order')->resid($order['orderid'])->success('获取支付链接成功');
        return $data['data']['payChannelUrl'];
        
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
                    Referer: https://pay.bilibili.com/payplatform-h5/cashierdesk.html
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
    
    public function getCookieValue($cookieString, $name = null) {
        // 分割cookie字符串为键值对数组
        $cookieParts = explode(';', $cookieString);
        $cookieArray = [];
        
        // 遍历每个cookie键值对
        foreach ($cookieParts as $cookie) {
            // 去除前后空格
            $cookie = trim($cookie);
            
            // 分割键值对
            $parts = explode('=', $cookie, 2);
            if (count($parts) != 2) {
                continue;
            }
            
            $cookieName = trim($parts[0]);
            $cookieValue = urldecode(trim($parts[1]));
            
            // 如果传入了name参数且匹配，直接返回值
            if ($name !== null && $cookieName === $name) {
                return $cookieValue;
            }
            
            // 添加到cookie数组
            $cookieArray[$cookieName] = $cookieValue;
        }
        
        // 如果传入了name参数但没有找到匹配的cookie，返回null
        if ($name !== null) {
            return null;
        }
        
        // 如果没有传入name参数，返回完整的cookie数组
        return $cookieArray;
    }
    
    public function queryOrder($order)
    {
        //Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1
        //订单状态 0=正在取码，1=取码失败，2=等待支付，3=支付超时，10=支付成功
        $ck = $order['account']['ck'];
        $ckinfo = $this->getCookieValue($ck);
        $http = Pro::http()
            ->url('https://api.live.bilibili.com/xlive/revenue/v1/order/queryOrderStatus')
            ->method('POST')
            ->header('Accept:application/json, text/plain, */*
                Content-Type:application/x-www-form-urlencoded
                Cookie:'. $ck .'
                Dnt:1
                Origin:https://live.bilibili.com/
                Referer:https://live.bilibili.com/
                Sec-Ch-Ua:"Not)A;Brand";v="24", "Chromium";v="116"
                Sec-Ch-Ua-Mobile:?0
                Sec-Ch-Ua-Platform:"iPhone"
                Sec-Fetch-Dest:empty
                Sec-Fetch-Mode:cors
                Sec-Fetch-Site:same-site
                User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 17_0_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1')
            ->formData([
                'order_id'   => $order['syorder'],
                'csrf_token' => $ckinfo['bili_jct'],
                'csrf'       => $ckinfo['bili_jct'],
                'visit_id'   => '6p8kg336ank0',
            ])
            //->lsp_tps()
            ->ex();
        Pro::logger('order')->resid($order['orderid'])->info('接口回调',$http->getBody());
        if(!$http->getBody()){
            Pro::logger('order')->resid($order['orderid'])->error('回调查询失败1');
            return false;
        }
        $data = $http->getJson();
        if(!isset($data['data']['status'])){
            Pro::logger('order')->resid($order['orderid'])->error('回调查询失败2');
            return false;
        }
        if($data['data']['status'] == 5){
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
<?php
namespace app\api\controller;

use Webman\RedisQueue\Client;
use support\Request;
use support\Response;
use Bilulanlv\ThinkCache\facade\ThinkCache;
use app\api\traits\Pro;


use think\facade\Cache;

class Pay extends Api
{
    
    protected $noNeedLogin = ['index','payment'];
    
    protected $model   = null;
    protected $user    = null;
    protected $account = null;
    protected $access  = null;
    protected $order   = null;
    protected $system  = null;
    protected $http    = null;
    
    public function __construct(Request $request = null)
    {
        parent::__construct();
        $this->model    = new \app\api\model\System;
        $this->user     = new \app\api\model\User;
        $this->account  = new \app\api\model\Account;
        $this->order    = new \app\api\model\Order;
        
    }
    
    public function bulid(Request $request)
    {
        $params     = $request->get();
        $appid      = $request->get('appid');
        $suporder   = $request->get('suporder');
        $fee        = $request->get('fee');
        $code       = $request->get('code');
        $mid        = $request->get('mid');
        $pay_type   = $request->get('pay_type');
        $return_url = $request->get('return_url');
        $notify_url = $request->get('notify_url');
        $ip         = $request->get('ip');
        $sign       = $request->get('sign');
        $timestamp  = $request->get('timestamp');
        $params['ip'] = null;
        
        $validate = \Tinywan\Validate\Facade\Validate::rule([
            'appid'      => 'require',
            'suporder'   => 'require|max:30',
            'fee'        => 'require|number',
            'pay_type'   => 'require|in:alipay,wxpay',
            'timestamp'  => 'require|number',
            'sign'       => 'require',
        ]);
        
        if (!$validate->check($params)){
            $this->error($validate->getError());
        }
        
        $user = $this->user->where(['appid' => $params['appid']])->find();
        if(!$user){
            $this->error('appid参数错误'.$params['appid']);
        }
        
        if(!$this->model->vlidataSign($appid.$user->appkey.$suporder.$fee.$timestamp, $sign)){
            $this->error('签名验证失败');
        }
        
        if($user->pid > 0){
            $this->error('SUP权限未开通');
        }
        
        if($user->fee < 0){
            $this->error('SUP余额不足');
        }
        
        $query = [
            'user_id'  => $user['id'],
            'fee'      => $fee,
            'mid'      => $mid,
            'pay_type' => $pay_type,
            'code'     => $code
        ];

        $order = $this->order->start($user,$params,$query);
        if(!$order){
            $this->error($this->order->getError());
        }
        
        $result = [
            'orderid' => $order['orderid'],
            'url'     => sconfig('app_url') .'/api/pay/payment?orderid='. $order['orderid'],
        ];
        
        $this->success('ok',$result);
        
        
    }
    
    public function ev(Request $request)
    {
        $uri  = request()->path();
        $sha1 = explode('/',$uri);

        if(count($sha1) != 3){
            $this->error('error');
        }
        
        $payment = Pro::model('Payment')->where('sha1',$sha1[2])->find();
        if(!$payment){
            $this->error('error');
        }
        
        $params = $request->post();
        if($params){
            $params['ip'] = Pro::ip();
            $fee    = $request->post('fee');
            $method = $request->post('method');
            if(!$fee || !$method){
                $this->error('缺少参数');
            }
            
            if(!is_numeric($fee)){
                $this->error('非法金额');
            }

            $query = [
                'user_id'  => $payment['user_id'],
                'fee'      => $fee,
                'mid'      => '',
                'pay_type' => $method,
                'code'     => $payment['access']['code']
            ];
    
            $order = $this->order->start($payment['user'],$params,$query);
            if(!$order){
                $this->error($this->order->getError());
            }
            
            $result = [
                'orderid' => $order['orderid'],
                'url'     => sconfig('app_url') .'/api/pay/payment?orderid='. $order['orderid'],
            ];
            
            $this->success('ok',$result);
            
        }
        
        $className = strtolower($payment->access->module);
        return view('pay/'.$className.'/expay',['name' => 'webman']);
        
        return $sha1[2];
        
    }
    
    public function query_ev_order(Request $request)
    {
        $orderid  = $request->get('orderid');
        $order    = Pro::model('Order')->where('orderid',$orderid)->find();
        
        if(!$order){
            $this->error('订单不存在');
        }
        
        if($order['status'] ==  1){
            $this->error('订单下单失败');
        }
        
        if($order['status'] ==  0 && $order['exptime'] < time()){
            $this->error('订单下单失败');
        }
        
        if($order['status'] == 3 ){
            $this->error('订单已超时');
        }
        
        if(!$order->city){
            $ip = Pro::ip();

            $ip2region        = new \os\iplib\Ip2Region;
            $info             = $ip2region->btreeSearch($ip);
            $order->ua        = detectDevice($request->header('user-agent'));
            $order->city      = $info;
            $order->ip        = $ip;
            $order->open_time = time();
        }
        
        $order->stay_time = time();
        $order->save();
        $ts = $order['exptime'] - time();
        $payinfo = [];
        if($order['payurl']){
            $payinfo = json_decode($order['payurl'],true);
        }
        
        $result = [
            'orderid' => $order['orderid'],
            'payinfo' => $payinfo,
            'status'  => $order['status'],
            'fee'     => $order['fee'],
            'timeout' => $ts <= 0 ? 0 : $ts
        ];
        
        $this->success('ok',$result);
        
    }
    
    public function sub_order(Request $request)
    {
       
        $params     = $request->get();
        $appid      = $request->get('appid');
        $fee        = $request->get('fee');
        $code       = $request->get('code');
        $pay_type   = $request->get('pay_type');
        $ip         = $request->get('ip');
        
        $user = $this->user->where(['appid' => $params['appid']])->find();
        if(!$user){
            $this->error('appid参数错误'.$params['appid']);
        }
        
        if(!$this->model->vlidataSign($appid.$user->appkey.$suporder.$fee.$timestamp, $sign)){
            $this->error('签名验证失败');
        }
        
        if($user->pid > 0){
            $this->error('SUP权限未开通');
        }
        
        if($user->fee < 0){
            $this->error('SUP余额不足');
        }
        
        $query = [
            'user_id'  => $user['id'],
            'fee'      => $fee,
            'mid'      => $mid,
            'pay_type' => $pay_type,
            'code'     => $code
        ];

        $order = $this->order->start($user,$params,$query);
        if(!$order){
            $this->error($this->order->getError());
        }
        
        $result = [
            'orderid' => $order['orderid'],
            'url'     => sconfig('app_url') .'/api/pay/payment?orderid='. $order['orderid'],
        ];
        
        $this->success('ok',$result);
        
    }
    
    public function usee(Request $request)
    {
        
        return view('pay/payment');
        
    }
    
    public function payment(Request $request)
    {
        
        if(!Request()->header('x-forwarded-for')){
            //return 'ERROR:请返回源站';
        }
        
        $orderid = $request->get('orderid');
        $order = $this->order->where('orderid',$orderid)->find();
        if(!$order){
            //return 'NONE:请返回源站';
        }
        $latesTime = $order->exptime + 300;
        if(time() > $latesTime){
            //return 'ERROR:请返回源站';
        }
        
        if(!$order->city){
            $ip = Pro::ip();

            $ip2region        = new \os\iplib\Ip2Region;
            $info             = $ip2region->btreeSearch($ip);
            $order->ua        = detectDevice($request->header('user-agent'));
            $order->city      = $info;
            $order->ip        = $ip;
            $order->open_time = time();
            $order->save();
        }

        return view('pay/payment');
        
    }
    
    public function paymentv1(Request $request)
    {

        if(!Request()->header('x-forwarded-for')){
            return 'ERROR:请返回源站';
        }
        
        $orderid = $request->get('orderid');
        $order = $this->order->where('orderid',$orderid)->find();
        if(!$order){
            return 'NONE:请返回源站';
        }
        $latesTime = $order->exptime + 300;
        if(time() > $latesTime){
            return 'ERROR:请返回源站';
        }
        
        if(!$order->city){
            $ip = Pro::ip();

            $ip2region        = new \os\iplib\Ip2Region;
            $info             = $ip2region->btreeSearch($ip);
            $order->ua        = detectDevice($request->header('user-agent'));
            $order->city      = $info;
            $order->ip        = $ip;
            $order->open_time = time();
            $order->save();
        }

        return view('pay/payment');
        
    }
    
    public function test_payment(Request $request)
    {

        $orderid = $request->get('orderid');
        $order = $this->order->where('orderid',$orderid)->find();
        if(!$order){
            return 'NONE:请返回源站';
        }
        $latesTime = $order->exptime + 300;
        if(time() > $latesTime){
            return 'ERROR:请返回源站';
        }
        if(!$order->city){
            $ip = Pro::ip();
            $ip2region = new \os\iplib\Ip2Region;
            $info = $ip2region->btreeSearch($ip);
            $order->city = $info;
            $order->ip = $ip;
            $order->open_time = time();
            $order->save();
        }

        return view('pay/test_payment');
        
    }
    
    public function feedback(Request $request)
    {
        
        return view('pay/feedback');
        
    }
    
    public function send_feedback(Request $request)
    {
        $orderid = $request->post('orderid');
        $image   = $request->file('image');
        $content = $request->post('content');
        
        /*if(!$orderid){
            $this->error('提交失败');
        }
        
        if(!$image && !$content){
            $this->error('图片与描述必须提交任意一个');
        }
        
        $order = $this->order->getCache($orderid);
      
        if(!$order){
            $this->error('订单不存在');
        }
        
        $is = Pro::model('Feedback')->where('sha1' , sha1($orderid.time()))->find();
        if($is){
            $this->error('提交过于频繁');
        }
        
        $count = Pro::model('Feedback')->where('orderid' , $orderid )->count();
        if($count > 5){
            $this->error('请勿频繁提交');
        }*/
        
        $data = [
            'orderid' => $orderid,
            'content' => $content,
            'sha1' => sha1($orderid.time())
        ];
        
        if($image){
           
           // 验证文件是否存在
            if (!$image || !$image->isValid()) {
                $this->error('无效的文件上传');
            }
     
            // 二次验证文件类型
            $imageInfo = @getimagesize($image->getPathname());
            if ($imageInfo === false) {
                $this->error('文件内容不符合图片');
            }

            // 生成安全存储路径
            $saveDir   = public_path() . '/uploads/';
            $extension = $image->getUploadExtension() ?: 'tmp'; // 默认扩展名

            $filename  = date('Ymd') . '/' . sha1($image->getUploadName()) . '.' . $extension;

            $image->move($saveDir . $filename);
            
            $data['image'] = sconfig('api_url') . '/uploads/' . $filename;
            
        }
        
        $result = Pro::model('Feedback')->create($data);
        
        if($result){
            $this->success();
        }
        
        $this->error('请重新提交');
        
    }
    
    public function queryOrder(Request $request)
    {
        
        $json = decryptString($request->get('packet'));
        $data = json_decode($json,true);
        
        if(!isset($data['orderid'])  ||  !isset($data['ping_time'])){
            return Pro::enError('请返回源站');
        }
        
        $orderid   = $data['orderid']   ;
        $ping_time = $data['ping_time'] ;
        
        //$order = $this->order->getCache($orderid);
        $order = Pro::model('Order')->where('orderid',$orderid)->find();
        if(!$order){
            return Pro::enError('订单不存在');
        }
        
        if($order->error_info && in_array($order->status,[1,3])){
            return Pro::enError($order->error_info);
        }
        
        $exptime = $order['exptime'];
        if($order['status'] == 2){
            $exptime -= 30;
        }
        
        if(time() > $exptime){
            return Pro::enError('订单超时1');
        }
        if($order['status'] == 3){
            return Pro::enError('订单超时2');
        }
        if($order['status'] == 1){
            return Pro::enError('订单匹配失败');
        }
        
        if($ping_time){
            if(is_numeric($ping_time) || $ping_time < 100000){
                $order->ping_time = $ping_time;
            }
        }

        $order->stay_time = time();
        $order->save();
        
        $result = [
            'orderid'   => $order['orderid'],
            'create_at' => $order['createtime'],
            'fee'       => $order['fee'],
            'status'    => $order['status'],
            'exptime'   => $exptime - time(),
            'payurl'    => $order['payurl'],
            'return_url'=> $order->notify_info['return_url'],
            'tpl'       => isset($order['access']['pay_tpl']) ? $order['access']['pay_tpl'] : 'payment',
            'ping_time' => round(microtime(true) * 1000),
        ];
        
        return Pro::enSuccess($order->error_info,$result);
        
    }
    
    public function tten(Request $request)
    {
        
        return Pro::enSuccess('no');
        
    }
    
    public function testqueryOrder(Request $request)
    {
        
        $json = decryptString($request->get('packet'));
        $data = json_decode($json,true);
        
        if(!isset($data['orderid'])  ||  !isset($data['ping_time'])){
            return Pro::enError('请返回源站');
        }
        
        $orderid   = $data['orderid']   ;
        $ping_time = $data['ping_time'] ;
        
        //$order = $this->order->getCache($orderid);
        $order = Pro::model('Order')->where('orderid',$orderid)->find();
        if(!$order){
            return Pro::enError('订单不存在');
        }
        
        if($order->error_info && in_array($order->status,[1,3])){
            return Pro::enError($order->error_info);
        }
        
        $exptime = $order['exptime'];
        if($order['status'] == 2){
            $exptime -= 30;
        }
        
        if(time() > $exptime){
            return Pro::enError('订单超时1');
        }
        if($order['status'] == 3){
            return Pro::enError('订单超时2');
        }
        if($order['status'] == 1){
            return Pro::enError('订单匹配失败');
        }
        
        if($ping_time){
            if(is_numeric($ping_time) || $ping_time < 100000){
                $order->ping_time = $ping_time;
            }
        }
        
        $order->stay_time = time();
        $order->save();
        
        $result = [
            'orderid'   => $order['orderid'],
            'create_at' => $order['createtime'],
            'fee'       => $order['fee'],
            'status'    => $order['status'],
            'exptime'   => $exptime - time(),
            'payurl'    => $order['payurl'],
            'return_url'=> $order->notify_info['return_url'],
            'tpl'       => isset($order['access']['pay_tpl']) ? $order['access']['pay_tpl'] : 'payment',
            'ping_time' => round(microtime(true) * 1000),
        ];
        
        return Pro::enSuccess($order->error_info,$result);
        
    }
    
    public function ping(Request $request)
    {
        
        $orderid = $request->get('orderid');
        if(!$orderid){
            return;
        }
        
        $order = Pro::model('Order')->where('orderid',$orderid)->find();
      
        if($order){
            $order->click_time = time() - $order->open_time;
            $order->save();
        }
        
    }
    
    public function query(Request $request){
        
        $appid      = $request->get('appid');
        $suporder   = $request->get('suporder');
        $timestamp  = $request->get('timestamp');
        $sign       = $request->get('sign');
        
        $user = $this->user->where(['appid' => $appid])->find();
        if(!$user){
            $this->error('appid参数错误');
        }
        
        if(!$this->model->vlidataSign($appid.$user->appkey.$suporder.$timestamp, $sign)){
            $this->error('签名验证失败');
        }
        
        $order = $this->order->where('sha1' , sha1($appid . $suporder))->find();
        
        if(!$order){
            $this->error('订单不存在');
        }
        
        $result = [
            'orderid' => $order->orderid,
            'suporder'=> $order->suporder,
            'fee'     => $order->fee,
            'status'  => $order->status,
            'exptime' => $order->exptime,
        ];
        
        $this->success('ok',$result);
        
    }
    
    public function sss(Request $request){
        
        $response = Pro::http()->method('GET')
                    ->header('x-forwarded-for:123.123.123')
                    ->url('http://192.140.161.107/')
                    ->ex();
        return $response->getBody();
        
    }
    
    public function qrcode(Request $request){
        $base = \config('backend.admin_base_url', '');
        if ($base === '' || $base === null) {
            return \response('', 404);
        }
        $query = $request->query();
        $url = rtrim($base, '/') . '/qrcode/build?' . http_build_query($query);
        $txt = @file_get_contents($url);
        if ($txt === false) {
            return \response('', 502);
        }
        return \response($txt, 200, ['Content-Type' => 'image/png']);
    }
    
    public function sync_callback(Request $request){
        $key = $request->post('key');
        $orderid = $request->post('orderid');
        $expectKey = \config('backend.sync_callback_key', '');
        if ($expectKey === '' || $key !== $expectKey) {
            return '失败';
        }
        
        $result = Pro::model('Order')->callbackHttp($orderid);
        
        if($result){
            return '任务已执行';
        }
        
        return '执行失败，请重试';
        
    }
    
    public function pendent()
    {
        $orderid = request()->get('orderid');
        $uri = request()->path();
        if(!$orderid){
            return;
        }
        
        $order = Pro::model('Order')->where('orderid',$orderid)->find();

        if(!$order){
            return;
        }
        
        $className = strtolower($order->access->module);
        return view('pay/'.$className.'/index');
        if(count($uri) != 6){
            return 'error';
        }
        $className  = $uri[4];
        $methodName = $uri[5];
        
        

    }
    
    public function app()
    {
        $orderid = request()->get('orderid');
        $uri = request()->path();
        if(!$orderid || !$uri){
            return;
        }
        return 456;
        $order = Pro::model('Order')->where('orderid',$orderid)->find();

        if(!$order){
            return;
        }
        
        $uri = explode('/',$uri);
        if(count($uri) != 6){
            return 'error';
        }
        $className  = $uri[4];
        $methodName = $uri[5];
        
        return view('pay/'.$className.'/'.$methodName);

    }
    
    public function serve(Request $request)
    {
        // 获取路径参数，例如：pay/res/1.css
        $path = $request->route('path');
        
        // 拼接完整文件路径：app/api/view/pay/res/1.css
        $file = app_path("api/view/{$path}");
        
        // 检查文件是否存在
        if (!is_file($file)) {
            return response('File not found', 404);
        }

        // 返回文件
        return response()->file($file);
    }
    
    
    
    
}
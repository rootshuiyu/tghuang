<?php
namespace app\api\controller;

use Webman\RedisQueue\Client;
use support\Request;
use Bilulanlv\ThinkCache\facade\ThinkCache;


use think\facade\Cache;

class Dock extends Api
{
    
    protected $model   = null;
    protected $user    = null;
    protected $account = null;
    protected $access  = null;
    protected $order   = null;
    
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
      
        
        $this->success($request->getRealIp());
        
        
        
    }
    
    public function return_url(Request $request)
    {
        
        $this->success('ok');
        
        
    }
    
    public function notify(Request $request)
    {
        
        $this->success('ok');
        
        
    }
    
    public function okpay(Request $request)
    {
        
        
        
        $http  = http_post([
                'url'  => 'http://198.44.167.37/inside/uploadOrderRg',
                //'header' => 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundarymfxJS4Lf6CnWggOr',
                'type' => 'json',
                'body' => [
                    'goodsId'     => '10',
                    'sumprice'    => 1,
                    'paytype'     => 4,
                    'payId'       => 0,
                    'thisrate'    => 0,
                    'thisratemin' => 0,
                    'thisratetop' => 0,
                    'iswho'       => 2,
                ]
            ]);
        
        if($http['code'] != 1000){
            //ERROR
            $this->success('ok',$http);
        }
        
        $http_pay = http_post([
                'url'  => 'http://198.44.167.37/pay/getPayOnline',
                //'header' => 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundarymfxJS4Lf6CnWggOr',
                'type' => 'json',
                'body' => [
                    'p'     => $http['pm'],
                ]
            ]);
        
        $this->success('ok',$http_pay['payUrl']);
        
        
    }
    
    public function queryok(Request $request)
    {
        
        $http = http_post([
            'url'  => 'http://198.44.167.37/api/sup/dualOrder.htm',
            'type' => 'json',
            'body' => [
            'appId'     => '11073',
            'number'    => '20250323013339324',
            'status'    => 2,
            'returnInfo' => '正在充值',
            'sign'    => md5('da0660ec18c76a38154b2024dffa431e'.'11073'.'20250323013339324'.'2'),
            ]
        ]);
        $this->success('ok', $http );
        return;
        $query = http_post([
                'url'  => 'http://198.44.167.37/inside/getVisitorOrderDetail?orderNumber=20250321224803160',
                //'header' => 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundarymfxJS4Lf6CnWggOr',
                'type' => 'json',
                'body' => [
                    'keyWord'     => '20250321224803160',
                    'keyValue'    => 1
                ]
            ]);
        
        $this->success('ok',$query['o']['State']);
        
        
    }
    
}
<?php

namespace app\controller;
use Webman\RedisQueue\Client;
use support\Request;
use support\Response;
use Bilulanlv\ThinkCache\facade\ThinkCache;
use app\api\traits\Pro;


class Index
{
    public function index(Request $request)
    {
       return 'rest-success';
       $user = \app\common\model\v2\User::find(50729);
       
       //return json($user);
       return view('index/view', ['name' => 'webman']);
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function ev(Request $request)
    {
        $uri  = request()->path();
        $sha1 = explode('/',$uri);

        if(count($sha1) != 3){
            return 'error';
        }
        
        $payment = Pro::model('Payment')->where('sha1',$sha1[2])->find();
        if(!$payment){
            return "ERROR";
        }

        $className = strtolower($payment->access->module);
        return view('pay/'.$className.'/expay',['name' => 'webman']);
        
        return $sha1[2];
        
    }

}

<?php

namespace tencent;
use think\Db;

class Wechat
{
    private $appid = 'wx1d368c2ed69c9176'; //默认公众号APPID
    private $appkey = '36a74b973751c44159cbdab9d15726a5';

    public function __construct($sdkappid='', $key='')
    {
        if($sdkappid){
            $this->appid = $sdkappid;
            $this->appkey = $key;
        }
    }

    /**
     * 【功能说明】获取access_token
     */
    public function access_token()
    {
        $db = Db::name('sysconfig')->where('name','access_token')->find();
        if(time() > $db['endtime'] || !$db['value']){
            return $this->get_access_token();
        }
        return $db['value'];
        
    }
    
    /**
     * 【功能说明】获取最新access_token
     */
    private function get_access_token()
    {
        //获取 access_token
        $appid = $this->appid;
        $appkey = $this->appkey;
        $access_result = file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appkey}");
        $access_result = json_decode($access_result,true)['access_token'];
        Db::name('sysconfig')->where('name','access_token')->update(['value' => $access_result ,'endtime' => time()+7000]);
        return $access_result;
        
    }
    
    /**
     * 【功能说明】获取 jsapi_ticket
     */
    public function jsapi_ticket()
    {
        $db = Db::name('sysconfig')->where('name','jsapi_ticket')->find();
        if(time() > $db['endtime'] || !$db['value']){
            return $this->get_jsapi_ticket();
        }
        return $db['value'];
        
    }
    
    /**
     * 【功能说明】获取最新 jsapi_ticket
     */
    private function get_jsapi_ticket()
    {
        //获取jsapi_ticket
        $ticket = file_get_contents('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='. $this->access_token() .'&type=jsapi');
        $ticket = json_decode($ticket,true)['ticket'];
        
        Db::name('sysconfig')->where('name','jsapi_ticket')->update(['value' => $ticket ,'endtime' => time()+7000]);
        return $ticket;
        
    }
 
}

<?php

namespace tencent;

class Rapidauth
{
    
    private $sdkappid = '';
    private $appkey = '';
    private $random = '';
    private $time = '';

    public function __construct()
    {
        $this->sdkappid = '1400920386';
        $this->appkey = '2b6a626b04eca3520ac407e593000285';
        $this->random = rand(1000000,9999999);
        $this->time = time();
    }
    
    public function validate($token,$carrier){
        if(!in_array($carrier,['mobile','unicom','telecom'])){
            return false;
        }
        $url = 'https://yun.tim.qq.com/v5/rapidauth/validate?sdkappid='.$this->sdkappid.'&random='.$this->random;
        $body = [
            'sig' => $this->sig(),
            'time' => $this->time,
            'carrier' => $carrier,//运营商，移动：mobile， 联通：unicom，电信：telecom,
            'token' => $token
        ];
        $body = json_encode($body);
        $http = $this->http_post($url,$body);
        //print_r($http);
        if($http['result'] == 0){
            return $http['mobile'];
        }
        return false;   
    }
    
    public function sig(){
        return hash('sha256','appkey='.$this->appkey.'&random='.$this->random.'&time='.$this->time);
        
    }
    
    private function http_post($url, $body)
    {

        $curl = curl_init();
        //设置提交的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        $post_data = $body;
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return json_decode($data, true);
    }
    
    
}
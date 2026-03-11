<?php
namespace tencent;
class Face
{
    const SecretId = "";   // 通过环境变量 TENCENT_SECRET_ID 或 config/tencent.php 配置
    const SecretKey = "";  // 通过环境变量 TENCENT_SECRET_KEY 或 config/tencent.php 配置
    const Url = "https://faceid.tencentcloudapi.com"; 
    
    const app_id = '';     // H5人脸识别 WBappid，通过配置注入
    const secret = '';     // H5人脸识别 Secret，通过配置注入
    const nonce = '';      // 随机字符串，通过配置注入
 
    //算法
    const Algo = "sha256";
    //规范请求串
    const HTTPRequestMethod = "POST";
    const CanonicalURI = "/";
    const CanonicalQueryString = "";
    const CanonicalHeaders = "content-type:application/json; charset=utf-8\nhost:faceid.tencentcloudapi.com\n";
    const SignedHeaders = "content-type;host";//参与签名的头部信息
 
    //签名字符串
    const Algorithm = "TC3-HMAC-SHA256";
    const Service = "faceid";
    const Stop = "tc3_request";
 
    /**
     * 实名核身鉴权 ID/身份证号码/微信h5人脸
     */
    public function getDetectAuth($Name,$IdCard)
    {
        $param = [
            'RuleId' => "1",//用于细分客户使用场景，申请开通服务后，可以在腾讯云慧眼人脸核身控制台（https://console.cloud.tencent.com/faceid） 自助接入里面创建，审核通过后即可调用
            'Name' => $Name,
			'IdCard' => $IdCard,//DetectAuth 微信h5人脸 、IdCardVerification 身份证号姓名识别
	   ];
        return self::getCommonPostRequest("DetectAuth", $param);
    }
    
    /**
     * 实名核身鉴权 ID/身份证号码
     */
    public function IdCardVerification($Name,$IdCard)
    {
        $param = [
            //'RuleId' => "1",//用于细分客户使用场景，申请开通服务后，可以在腾讯云慧眼人脸核身控制台（https://console.cloud.tencent.com/faceid） 自助接入里面创建，审核通过后即可调用
            'Name' => $Name,
			'IdCard' => $IdCard,//DetectAuth 微信h5人脸 、IdCardVerification 身份证号姓名识别
	   ];
	   
	   $response = self::getCommonPostRequest("IdCardVerification", $param);
	   
	   if($response['data']['Result'] == 0 ){
	       $response['data']['Result'] = 1;
	   }
	   
       return ['code'=>$response['data']['Result'],'msg'=>$response['data']['Description']];
    }
 
 
    /**
     * 鉴权
     * @param string $action 方法
     * @param array $param 参数
     * @param string $version 版本号
     * @return array
     */
    private static function getCommonPostRequest($action, array $param = [], $version = "2018-03-01")
    {
        //时间戳
        $timeStamp = time();
        //$timeStamp       =   1586333773;
        //参数转化Json
        $paramJson = json_encode($param);
        //规范请求串
        $hashedRequestPayload = self::HashEncryption($paramJson);
        $canonicalRequest = self::HTTPRequestMethod . "\n" .
            self::CanonicalURI . "\n" .
            self::CanonicalQueryString . "\n" .
            self::CanonicalHeaders . "\n" .
            self::SignedHeaders . "\n" .
            $hashedRequestPayload;
        //签名字符串
        $date            =   gmdate("Y-m-d", $timeStamp);//UTC 0时区的值
        $credentialScope = $date . "/" . self::Service . "/" . self::Stop;
        $hashedCanonicalRequest = self::HashEncryption($canonicalRequest);
        $stringToSign = self::Algorithm . "\n" .
            $timeStamp . "\n" .
            $credentialScope . "\n" .
            $hashedCanonicalRequest;
 
 
        //计算签名
        $secretDate = self::HashHmacSha256Encryption($date, 'TC3' . self::SecretKey);
        $secretService = self::HashHmacSha256Encryption(self::Service, $secretDate);
        $secretSigning = self::HashHmacSha256Encryption(self::Stop, $secretService);
 
        //签名
        $signature = self::HashHmacSha256Encryption($stringToSign, $secretSigning, false);
        
        $authorization = self::Algorithm . ' ' .
            'Credential=' . self::SecretId . '/' . $credentialScope . ', ' .
            'SignedHeaders=' . self::SignedHeaders . ', ' .
            'Signature=' . $signature;
        //Header头部
        $headers = [
            "Authorization: $authorization",
            "Host: faceid.tencentcloudapi.com",
            "Content-Type: application/json; charset=utf-8",
            "X-TC-Action: $action",
            "X-TC-Version: $version",
            "X-TC-Timestamp: $timeStamp",
            "X-TC-Region: ap-beijing"
        ];
        //请求
        $response = self::get_curl_request(self::Url, $paramJson, self::HTTPRequestMethod, $headers);
        
        //解析
        if (!$response) {
            return ['code' => 0, 'codeError' => '1002', 'msg' => 'Interface request failed'];
        }
        $response = json_decode($response, true);
        if (!isset($response['Response'])) {
            return ['code' => 0, 'codeError' => '1003', 'msg' => 'Response error'];
        }
        if (isset($response['Response']['Error'])) {
            return [
                'code' => 0
                , 'codeError' => $response['Response']['Error']['Code']
                , 'msg' => $response['Response']['Error']['Message']
                , 'RequestId' => $response['Response']['RequestId']
            ];
        } else {
            return ['code' => 1, 'msg' => 'ok', 'data' => $response['Response']];
        }
    }
 
    private static function HashEncryption($sign)
    {
        return strtolower(hash(self::Algo, $sign));
    }
 
    private static function HashHmacSha256Encryption($sign, $key, $flag = true)
    {
        return hash_hmac(self::Algo, $sign, $key, $flag);
    }
 
    /**
     * @param $url
     * @param array $param
     * @param string $mothod
     * @param array $headers
     * @param int $return_status
     * @param int $flag
     * @return array|bool|string
     */
    public static function get_curl_request($url, $param = [], $mothod = 'POST', $headers = [], $return_status = 0, $flag = 0)
    {
        $ch = curl_init();
        if (!$flag) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (strtolower($mothod) == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        } else {
            $url = $url . "?" . http_build_query($param);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        #curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1"); //代理服务器地址
        #curl_setopt($ch, CURLOPT_PROXYPORT, 12639); //代理服务器端口
        $ret = curl_exec($ch);
        $code = curl_getinfo($ch);
        curl_close($ch);
        if ($return_status == "1") {
            return array($ret, $code);
        }
        return $ret;
    }
    
    /**
     * H5人脸核验
     * 获取 Access Token
     */
    public static function get_access_token()
    {
        
        $url = 'https://kyc.qcloud.com/api/oauth2/access_token?app_id='. self::app_id .'&secret='. self::secret .'&grant_type=client_credential&version=1.0.0';
        $content = json_decode(file_get_contents($url),true);
        
        if($content['success'] != 1){
           return false; 
        }
        return $content['access_token'];
        
    }
    
    /**
     * H5人脸核验
     * 获取 NONCE ticket
     */
    public static function get_ticket()
    {
        $access_token = self::get_access_token();
        
        if(!$access_token){
            return false;
        }
        
        $url = 'https://kyc.qcloud.com/api/oauth2/api_ticket?app_id='. self::app_id .'&access_token='. $access_token .'&type=SIGN&version=1.0.0';
        $content = json_decode(file_get_contents($url),true);
        //print_r($content);
        if($content['success'] != 1){
            return false;
        }
        return $content['tickets'][0]['value'];
    }
    
    /**
     * H5人脸核验
     * 获取 签名
     */
    public static function h5_sign($value = [])
    {
        $ticket = self::get_ticket();
        
        if(!$ticket){
            return false;
        }
        $param = [
            'appId' => self::app_id,
            'userId' => $value['userId'],
            'nonce' => self::nonce,
            'version' => '1.0.0',
            'ticket' => $ticket,
            'faceId' => $value['faceId'],
            'orderNo' => $value['orderNo'],
            ];
        sort($param); $str = '';
        foreach ($param as $v) {
            $str .= $v;
        }
        
        $str = sha1($str);
        //print_r($str);
        return $str;
    }
    
    /**
     * H5人脸识别入口
     */
    public function h5_bulid($Name,$IdCard,$orderNo,$userId)
    {
    
    $sign = self::h5_sign(['userId' => $userId]);
    
    $body = [
          'appId' => self::app_id,
          'orderNo' => $orderNo,
          'name' => $Name,
          'idNo' => $IdCard,
          'userId' => $userId,
          'version' => '1.0.0',
          'sign' => $sign,
          'nonce' => self::nonce,
          ];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://kyc.qcloud.com/api/server/getAdvFaceId?orderNo=".$orderNo);
        curl_setopt($ch, CURLOPT_HTTPHEADER,[
            'Content-Type: application/json;charset=UTF-8'
            ]); //设置头信息的地方
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);  //超时时间
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $response = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        
        $response = json_decode($response,true);
        
        return $response;
        
    }
    
    /**
     * H5人脸识别启动
     */
    public function h5_run($Name,$IdCard,$orderNo)
    {
        $userId = 'userID'.rand(100000,999999);
        $bulid = $this->h5_bulid($Name,$IdCard,$orderNo,$userId);
        
        if($bulid['code']!=0){
            return ['code'=>0,'msg'=>$bulid['msg']];
        }
          
        $sign = self::h5_sign([
          'faceId' => $bulid['result']['faceId'],
          'orderNo' => $orderNo,
          'userId' => $userId,
          ]);
          
        $body = [
            'appId' => self::app_id,
            'version' => '1.0.0',
            'nonce' => self::nonce,
            'orderNo' => $orderNo,
            'faceId' => $bulid['result']['faceId'],
            'url' => 'http://' .$_SERVER['HTTP_HOST'] . '/user/user_auth_result/',
            'resultType' => 1, //是否显示结果页面，参数值为“1”时直接跳转到 url 回调地址，null 或其他值跳转提供的结果页面
            'userId' => $userId,
            'sign' => $sign,
            'from' => 'browser', //browser：表示在浏览器启动刷脸；App：表示在 App 里启动刷脸，默认值为 App
            'redirectType' => '', //跳转模式，参数值为"1"时，刷脸页面使用 replace 方式跳转，不在浏览器 history 中留下记录；不传或其他值则正常跳转
            
            ];
          
        $str = http_build_query($body);
        $url = 'https://'. $bulid['result']['optimalDomain'] .'/api/web/login?'.$str;
          
        
        return [ 'code'=>1, 'url' =>$url];
    
        
        
    }
    
    /**
     * H5人脸识别启动
     */
    public function h5_result($orderNo)
    {
        
        $sign = self::h5_sign([
          'orderNo' => $orderNo,
          ]);
        
        $body = [
          'appId' => self::app_id,
          'orderNo' => $orderNo,
          'version' => '1.0.0',
          'sign' => $sign,
          'nonce' => self::nonce,
          ];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://kyc.qcloud.com/api/v2/base/queryfacerecord?orderNo=".$orderNo);
        curl_setopt($ch, CURLOPT_HTTPHEADER,[
            'Content-Type: application/json;charset=UTF-8'
            ]); //设置头信息的地方
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);  //超时时间
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $response = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        
        $response = json_decode($response,true);
        
        return $response;
        
    }

    
}
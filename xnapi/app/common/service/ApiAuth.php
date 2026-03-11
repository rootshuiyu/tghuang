<?php

namespace app\common\service;


use think\facade\Db;
use think\facade\Cache;
use app\common\model\v2\User;
use app\common\model\v2\UserToken;

/**
 * 用户鉴权服务
 * Class UserTokenService
 */
class ApiAuth
{
    protected static $instance = null;
    //允许输出
    protected $allowFields = ['id', 'username', 'nickname', 'mobile', 'avatar', 'score','gender','birthday','bio','successions','maxsuccessions','im','name','idcard','fee','money','trtc_sign','mid','showid'];
    
    /**
     * 类构造函数
     * class constructor.
     */
    public function __construct()
    {
        
        
    }
    
    public static function instance($options = [])
    {
        self::$instance = new static($options);

        return self::$instance;
    }
    
    public function init($token=null)
    {
        if(!$token){
            return [];
        }
        $data = $this->getToken($token);
        if(!$data){
            return [];
        }
        $user_id = intval($data['user_id']);
        $user = User::find($user_id);
        if(!$user){
            return [];
        }
        return $user;
        
    }
    
    private function getToken($token)
    {
       
       $data = UserToken::where('token',$this->TokenEncode($token))->find();

       if(!$data || $data['expiretime'] < time()){
           if($data){
              $data->delete(); 
           }
           return [];
       }
       
       $betime = $data['expiretime'] - 864000;
       if(time() > $betime){
           $data->expiretime = time() + 86400 * 30;
           $data->save();
       }
       
       return $data;
        
    }
    
    private function setToken($token='')
    {
        
    }
    
    private function TokenEncode($token)
    {
        $token_decode = hash_hmac('ripemd160',$token,'SFnyrAlsvpRDhGuWEPQKoZc6ktXNUgdY');
        return $token_decode;
        
    }

    /**
     * 校验token
     * @access protected
     * @param string $token
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function checkToken()
    {
        
    }
}
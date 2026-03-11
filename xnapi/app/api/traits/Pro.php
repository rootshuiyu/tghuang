<?php

namespace app\api\traits;
use Bilulanlv\ThinkCache\facade\ThinkCache;
use support\Request;

class Pro
{
    use \app\api\traits\ProTrait;
    
    public static function model($name = null)
    {
        $class = '\\app\\api\\model\\' . $name;
        $app = new $class;
        return $app;
    }
    
    public static function module($className) {
        
        // 构建动态类路径
        $moduleNamespace = '\app\api\module';
        $fullClassName = $moduleNamespace . '\\' . $className;
        $moduleInstance = new $fullClassName();
        
        return $moduleInstance;
        
    }
    
    public static function moduled($app,$function,$data = [],$de = 0){
        $re = [
            'app'    => $app,
            'action' => $function,
            'data'   => $data
        ];
        \os\AsyncQueue::pushTask('AsyncWork', $re,$de);
    }
    
    public static function console($text = null)
    {
        print_r($text."\n");
    }
    
    public static function sign($str){
       
       $hash = hash('sha256', $str);
       return $hash;
        
    }
    
    public static function limitQuery($name,$s = 10)
    {
        $key = 'query_'.$name;
        //print_r('-------匹配限制频率-------');
        $cache = ThinkCache::get($key);
        if(!$cache){
            ThinkCache::set($key,time(),$s);
            return true;
        }
        return false;
    }
    
    public static function exlock($name,$s = 20)
    {
        $key = 'lock_'.$name;
        $lock = ThinkCache::get($key);
        if($lock){
            return false;
        }
        ThinkCache::set($key,time(),$s);
        return true;
    }
    
    public static function setlock($name,$s = 20)
    {
        $key = 'lock_'.$name;
        ThinkCache::set($key,time(),$s);
        return true;
    }
    
    public static function unlock($name)
    {
        $key = 'lock_'.$name;
        ThinkCache::delete($key);
    }
    
    public static function ip()
    {
        
        $ips = Request()->header('x-forwarded-for');
        $ip = explode(',',$ips);
        return isset($ip[0]) ? $ip[0] : $ips;
        
    }
    
    public static function http()
    {
        
        return new \os\HttpRequest();
        
    }
    
    public static function worked($delay=0)
    {
        // 修复：创建ProDynamic实例后，使用initFromWorked方法正确初始化调用链
        $ProDynamic = new ProDynamic;
        $ProDynamic->delay($delay);
        return $ProDynamic;
        
    }
    
    public static function logger($name = null)
    {
        
        $logger = new \os\Logger();
        $logger->set($name);
        return $logger;
        
    }
    
    public static function enError($msg = 'ERROR')
    {
        
        $json = json_encode([\fast\Random::alnum(64) => '' ,'code' => 0, 'msg' => $msg , 'time' => time()]);

        $result = encryptString($json);
        
        return $result ;
        
    }
    
    public static function enSuccess($msg = null,$data = [])
    {
        
        $json = json_encode([\fast\Random::alnum(64) => '' ,'code' => 1, 'msg' => $msg ,'data' => $data , 'time' => time()]);

        $result = encryptString($json);
        
        return $result ;
    }
    
    public static function ip_city()
    {
        $ip = Request()->header('x-real-ip');
        $ip2region  = new \os\iplib\Ip2Region;
        $info       = $ip2region->btreeSearch($ip);
        return $info;
        
    }
    
}
<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;
use think\model\concern\Virtual;

class System extends Model
{
    use Virtual;
    
    public function vlidataSign($str,$sign){
        
        if($this->sign($str) != $sign){
            return false;
        }
        
        return true;
        
    }
    
    public function sign($str){
       
       $hash = hash('sha256', $str);
       return $hash;
        
    }
    
    public function setNotifyNumber($orderid)
    {
        
        $order = Order::where('orderid',$orderid)->find();
        $order->notify_number += 1;
        $order->save();
        
    }
    
    public function setStatus($orderid,$status)
    {
        
        $order = Order::where('orderid',$orderid)->find();
        $order->status = $status;
        $order->save();
        
    }
    
    public function moduleInstance($className = null) {
        
        // 构建动态类路径
        $moduleNamespace = '\app\api\module';
        $fullClassName = $moduleNamespace . '\\' . $className;
        $moduleInstance = new $fullClassName();
        
        
        return $moduleInstance;
        
    }
    
    public function console($msg = '')
    {
        
        print_r($msg."\n");
        
    }
    
    
}
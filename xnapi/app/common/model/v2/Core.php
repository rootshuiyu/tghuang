<?php

/**
 * 作者：2024-08-12 18:44:09
 * QQ：743395324
 * 2024-08-12 18:44:09
 */

namespace app\common\model\v2;

use think\model\concern\Virtual;
use think\Model;


class Core extends Model
{
    use Virtual;
    
    public function app($value = null){
        if(!$value){
            return;
        }
        return $this->hasMany($value);
        
    }
    
    public function in1() {  
        print_r($this);
        //print_r($this);
        return $this;
    }
    
    public static function asdsss($event)
    {
    	print_r($event);
    }
    
    public static function onAfterWrite($user)
    {
    	print_r('写入完成');
    }
    
    public static function onAfterInsert($user)
    {
		print_r('写入完成');
    }
    
}
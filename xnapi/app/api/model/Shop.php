<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Shop extends Model
{
    protected $name = 'shop';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    public function wallet()
    {
        
        
        
    }
    
    public function getItemAttr($value)
    {
        if(!$value){
            return [];
        }
        
        return json_decode($value,true);
        
        
    }
    
}
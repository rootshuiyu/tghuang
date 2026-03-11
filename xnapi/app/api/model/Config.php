<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Config extends Model
{
    protected $name = 'config';
    protected $pk = 'id';
    
    public static function onAfterRead($row)
    {
        if($row['type'] == 'array'){
            $row->value = json_decode($row->value,true);
        }
    }
    
    
    public function event($event){
        
        
        
    }
    
    
}
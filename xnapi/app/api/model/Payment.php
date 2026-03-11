<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Payment extends Model
{
    protected $name = 'payment';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
        
    }
    public function access()
    {
        return $this->hasOne(Access::class,'id','access_id');
        
    }
}
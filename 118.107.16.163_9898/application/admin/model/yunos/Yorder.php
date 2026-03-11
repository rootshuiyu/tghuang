<?php

namespace app\admin\model\yunos;

use think\Model;


class Yorder extends Model
{

    

    

    // 表名
    protected $name = 'yorder';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function access()
    {
        return $this->belongsTo('app\admin\model\Access', 'access_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function account()
    {
        return $this->belongsTo('app\admin\model\Account', 'account_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

<?php

namespace app\admin\model\yunos;

use think\Model;


class OrderLog extends Model
{ 

    // 表名
    protected $name = 'order_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

}

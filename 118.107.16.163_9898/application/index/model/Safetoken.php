<?php

namespace app\index\model;

use think\Model;


class Safetoken extends Model
{

    // 表名
    protected $name = 'safetoken';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];




}

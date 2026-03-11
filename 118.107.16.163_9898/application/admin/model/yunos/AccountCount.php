<?php

namespace app\admin\model\yunos;

use think\Model;
use think\Db;


class AccountCount extends Model
{

    // 表名
    protected $name = 'account_count';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    protected $updateTime = false;
    protected $deleteTime = false;
    
}

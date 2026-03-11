<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Fund extends Model
{
    protected $name = 'fund';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
}
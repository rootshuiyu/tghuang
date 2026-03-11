<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Access extends Model
{
    protected $name = 'access';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    
}
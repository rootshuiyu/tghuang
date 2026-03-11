<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Feedback extends Model
{
    protected $name = 'feedback';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
}
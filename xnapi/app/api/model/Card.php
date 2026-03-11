<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Card extends Model
{
    protected $name = 'card';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
}
<?php
namespace app\common\model\v2;

use think\Model;
use think\facade\Db;

class UserToken extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $name = 'user_token';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $pk = 'id';

}
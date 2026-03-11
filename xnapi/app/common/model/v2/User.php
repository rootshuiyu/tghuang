<?php
namespace app\common\model\v2;

use think\Model;
use think\facade\Db;

class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $name = 'user';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $pk = 'id';
    
    public function room()
    {
        return $this->belongsTo('\app\common\model\Room', 'mid', 'mid', [], 'LEFT')->setEagerlyType(0);
        
    }
    
    public static function getroom($mid)
    {
        $room = \app\common\model\Room::where(['mid'=> $mid])->find();
        return $room;
        
    }

}
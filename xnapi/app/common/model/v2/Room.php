<?php
namespace app\common\model\v2;

use think\Model;


class Room extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $name = 'room';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $pk = 'id';
    
    public function getNot(){
        
        return 123;
        
    }

}
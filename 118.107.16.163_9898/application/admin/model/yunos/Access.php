<?php

namespace app\admin\model\yunos;

use think\Model;
use fast\Random;


class Access extends Model
{

    // 表名
    protected $name = 'access';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
    public function url($user_id,$access_id)
    {
        $sha1 = md8($user_id . $access_id);
        if(!Payment::where('sha1',$sha1)->find()){
            Payment::create(['user_id' => $user_id , 'access_id' => $access_id ,'sha1' => $sha1]);
        }
        
        return md8($user_id . $access_id);
    }
    
    public static function init()
    {
        
        self::beforeUpdate(function ($row) {
            if($row->switch == 0){
                Account::where('access_id',$row->id)->update(['switch' => 0]);
            }
        });
    }

    public static function seachlist($is_boss = false)
    {
        $where = [];
        if(!$is_boss){
            $where['switch'] = 1;
        }
        
        $data = self::where($where)->order('id desc')->select();
        $result = [];
        foreach ($data as $v){
            $result[$v['id']] = $v['name'];
        }
        return $result;
        
    }
    
    public static function lists($is_boss = false)
    {
        $where['switch'] = 1;
        
        $data = self::where($where)->order('id desc')->select();
        
        return $data;
        
    }
    
    public function pool($value)
    {
        if(!$value){
            return ['title' => null , 'list' => [0]];
        }
        $value = explode('|',$value);
        $result = [
            'title' => isset($value[0]) ? $value[0] : null,
            'list'  => isset($value[1]) ? json_decode($value[1],true) : []
        ];
        
        return $result;
        
    }


}

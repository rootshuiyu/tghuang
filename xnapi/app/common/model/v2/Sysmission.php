<?php

namespace app\common\model\v2;

use think\Model;
use think\facade\Cache;
use think\facade\Db;

class Sysmission extends Model
{

    // 表名
    protected $name = 'sysmission';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;
    
    public static function onBeforeInsert($row)
    {
    	if($row['uniqueid']){
            $row['uniqueid'] = sha1(json_encode($row['content']));
        }
        $row['name'] = '';
    }
    
    public function searchlist()
    {
        $data = [
            'NoticeMsg' => '系统消息',
            'PorpFull' => '礼物全服广播',
            'Room101' => '刷新麦位', 
            'Room105' => '刷新房间信息',
            'Room106' => '刷新魅力值',
            'RoomNotify' => '房间消息',//101刷新麦位 106魅力值 105刷新房间信息, 110刷新PK
            'Disconnect' => '用户掉线',
        ];
        return $data;
    }
    
    public function setNameAttr($value,$data)
    {
        $searchlist = self::searchlist();
        $k = $data['method'];
        return $searchlist[$k];
        
    }
    
    public function setContentAttr($value,$data)
    {
        if(!$value){
            $value = [];
        }
        return json_encode($value);
        
    }
    
    public static function getContentAttr($value)
    {
        return json_decode($value,true);
        
    }
    
    public static function AddMiss($method='',$data = '',$uniqueid = '')
    {
        if(!$method) return;
        
        Db::startTrans();
        try {
            self::create([
                'method' => $method ,
                'content' => $data,
                'uniqueid' => $uniqueid,
                'user_id' => (isset($data['user_id'])) ? $data['user_id'] : ''
            ]);
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
        //Cache::rm($method.'_cache');
    }

    public function RemovedMiss($method,$id)
    {
        Cache::rm($method.'_cache');
        self::where(['id' => ['in',$id]])->delete();
        
    }
    
    public function DelUserMiss($method,$user_id)
    {
        self::where([ 'method' => $method ,'user_id' => ['in',$user_id]])->delete();
        Cache::rm($method.'_cache');
    }
    
    public function getMiss($method,$limit = 10)
    {
        
        //$data = Cache::get($method.'_cache');
        /*if(!$data){
            $data = self::where('method' , $method)->limit($limit)->select();
            foreach ($data as &$v){
                $v = $v->toArray();
            }
            Cache::set($method.'_cache',$data);
        }*/
        $data = self::where('method' , $method)->limit($limit)->select();
        return $data;
        
    }

}

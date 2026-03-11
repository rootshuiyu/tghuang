<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;
use Bilulanlv\ThinkCache\facade\ThinkCache;

class User extends Model
{
    protected $name = 'admin';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    public static function wallet($order)
    {
        
        $rate = $order->access->rate;
        
        $deduct = $rate * $order['fee'] / 100;
        //$value  = $order['fee'] + $deduct;
        $value = round($deduct,2);
        
        
        // 启动事务
        Db::startTrans();
        try {
            
            $user = self::where('id',$order['sup_user_id'])->lock(true)->find();
            /*if($user->pid > 0){
                $user = self::where('id',$user['pid'])->lock(true)->find();
            }*/
            $old = $user->fee;
            $user->fee -= $value;
            $user->save();
            
            $content = '【原'. $old .' => 变为 '.$user->fee.' 】-关联订单号-'.$order['orderid'];
            
            Fund::create([
                'user_id' => $user['id'],
                'action'  => 0,
                'type'    => 0,
                'value'   => $value,
                'content' => $content
            ]);
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        
        
    }
    
    public function valiedkey($key)
    {
        
        if(!$key){
            return null;
        }
        
        $data = $this->where('appkey',$key)->find();
        if(!$data){
            return null;
        }
        return $data['id'];
        
    }
    
    public function seachlist()
    {
        $key = 'users_cache';
        $cache = ThinkCache::get($key);
        if($cache){
            return $cache;
        }
        
        $list = $this->select();
        $result = [];
        foreach ($list as $user){
            
            $result[$user['id']] = $user['username'];
            
        }
        ThinkCache::set($key,$result,60);
        return $result;
        
    }
    
}
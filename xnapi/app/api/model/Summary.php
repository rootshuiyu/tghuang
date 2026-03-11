<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Summary extends Model
{
    protected $name = 'summary';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    public static function change($order)
    {
        
        // 启动事务
        Db::startTrans();
        try {
            $date = date("Y-m-d");
            $sha1 = sha1($order->user_id.$date);
            $data = self::where('sha1',$sha1)->find();
            if(!$data){
                self::create([
                    'user_id' => $order->user_id,
                    'date'    => $date ,
                    'value'   => $order->fee ,
                    'sha1'    => $sha1,
                ]);
            }else{
               $data->value += $order->fee;
               $data->save();
            }
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
    }
    
}
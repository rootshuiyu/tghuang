<?php

namespace app\admin\model\yunos;

use think\Model;
use think\Db;


class Summary extends Model
{

    // 表名
    protected $name = 'summary';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
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

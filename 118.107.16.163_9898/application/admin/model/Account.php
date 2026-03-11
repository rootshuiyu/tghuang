<?php

namespace app\admin\model;

use think\Model;

class Account extends Model
{
    // 表名
    protected $name = 'account';
    
    public static function change($order)
    {
        
        // 启动事务
        Db::startTrans();
        try {
            
            $account = self::where('id',$order->account_id)->find();
            if($account){
                $account->in_fee += $order->fee;
                $account->save();
            }
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        
    }
    
}

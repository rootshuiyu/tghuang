<?php

namespace app\admin\model;

use think\Model;
use think\Session;
use fast\Random;
use think\Db;
use app\admin\model\yunos\Summary;

class Admin extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $hidden = [
        'password',
        'salt'
    ];

    public static function init()
    {
        self::beforeWrite(function ($row) {
            
            if(empty($row->appid)){
                $row->appid = \fast\Random::numeric(8);
                $row->appkey = \fast\Random::uuid();
            }
            
            $changed = $row->getChangedData();
            //如果修改了用户或或密码则需要重新登录
            if (isset($changed['username']) || isset($changed['password']) || isset($changed['salt'])) {
                $row->token = '';
            }
        });
    }
    
    public static function seachlist($where)
    {
        
        $data = self::where($where)->select();
        $result = [];
        foreach ($data as $v){
            $result[$v['id']] = $v['nickname'];
        }
        return $result;
        
    }
    
    public static function wallet($order)
    {
        
        $rate = $order->access->rate;
        
        $deduct = $rate * $order['fee'] / 100;
        //$value  = $order['fee'] + $deduct;
        $value = round($deduct,2);
        
        // 启动事务
        Db::startTrans();
        try {
            
            $user = self::where('id',$order['user_id'])->lock(true)->find();
            if($user->pid > 0){
                $user = self::where('id',$user['pid'])->lock(true)->find();
            }
            $old = $user->fee;
            $user->fee -= $value;
            $user->save();
            
            $content = '手动回调【原'. $old .' => 变为 '.$user->fee.' 】-关联订单号-'.$order['orderid'];
            
            \app\admin\model\yunos\Fund::create([
                'user_id' => $order['user_id'],
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
    
    public function getSummary($user_id)
    {
        
        $day = Summary::where(['user_id' => $user_id,'date' => date('Y-m-d')])->value('value');
        
        $yesterdayTimestamp = strtotime('-1 day');
        // 格式化时间戳为日期字符串
        $yesterday = date('Y-m-d', $yesterdayTimestamp);
        
        $tday = Summary::where(['user_id' => $user_id,'date' => $yesterday ])->value('value');
        
        return [
            'day'  => $day,
            'tday' => $tday,
        ];
        
        
    }

}

<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;


class Qqorder extends Model
{
    protected $name = 'qq_order';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    public function yunOrder($data,$order)
    {
        $sup_qq = $order['account']['config']['sup_qq'];
        
        $ids = [];  $dataList = [];
        foreach ($data as $v){
            if($v['save']){
                
                $sha1 = sha1(
                $sup_qq
                . $v['balance']
                .$v['info']
                .$v['save']
                .$v['tranday']
                );
                $dataList[] = [
                    'name' => $sup_qq,
                    'fee'  => $v['save'],
                    'sha1' => $sha1,
                    'date'   => $v['tranday'],
                ];
            }
        }
        
        if(!$dataList){
            
            return;
        }
        
        $result = false;
        Db::startTrans();
        try {
     
            foreach ($dataList as $item) {
                $is_yun = $this->where('sha1',$item['sha1'])->find();
                if(!$is_yun){
                    $this->create($item);
                    $result = true;
                }
            }
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            // 处理异常
        }
        
        
        return $result;
        
    }
    
    public function checkPay($order)
    {
        $date[] = date("Y-m-d");
        if(date('H') == 0){
            $yesdaymap = strtotime('-1 day');
            $date[]   = date("Y-m-d",$yesdaymap);
            print_r('临界点');
        }
        //print_r($order->fee);
        $timestamp = strtotime($order['createtime']);
        $result = false;
        // 启动事务
        Db::startTrans();
        try {
            $sup_qq = $order['account']['config']['sup_qq'];

            $pay = $this->where([
                'name'   => $sup_qq ,
                'status' => 0,
                'fee'    => $order['fee'],
                ])->where('date','in',$date)->lock(true)->find();
            if($pay){
                $pay->status = 1;
                $pay->orderid = $order->orderid;
                $pay->save();
                $result = true;
                
            }
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        if($result){
            Order::settle($order['orderid']);
        }
        
        return $result;
    }
    
}
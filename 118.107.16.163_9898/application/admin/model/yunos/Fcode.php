<?php

namespace app\admin\model\yunos;

use think\Model;
use think\Db;


class Fcode extends Model
{

    // 表名
    protected $name = 'account';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;
    

    // 追加属性
    protected $append = [

    ];
    
    public static function init()
    {
        self::beforeWrite(function ($row) {
            
            if( empty($row->limit_max_fee) || $row->limit_max_fee == 0  ){
                $row->limit_max_fee = 9999999999;
                
            }
            
            if( empty($row->limit_fee)  ||  $row->limit_fee == 0 ){
                $row->limit_fee = 9999999999;
            }
            
        });
    }

    public function access()
    {
        return $this->belongsTo('app\admin\model\Access', 'access_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function user()
    {
        return $this->belongsTo('app\admin\model\Admin', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    
    public function getConfigAttr($value,$data)
    {
        
        $tpl = Access::get($data['access_id']);
        if(!$tpl->config_tpl){
            return '';
        }
        
        $arr = json_decode($tpl->config_tpl,true);
        
        
        $config = $value ? json_decode($value,true) : [];
        
        $list = [];
        foreach ($config as $vo){
            $list[$vo['key']] = $vo['value'];
        }
        $result = [];
        foreach ($arr as $v){
            
            $result[] = [
                'title' => $v['title'],
                'key'   => $v['key'],
                'value' => !empty($list[$v['key']]) ? $list[$v['key']] : '',
            ];
            
        }
        
        
        
        return json_encode($result);
        
    }
    
    public static function seachlist()
    {
        
        $data = self::select();
        $result = [];
        foreach ($data as $v){
            $result[$v['id']] = $v['name'];
        }
        return $result;
        
    }
    
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

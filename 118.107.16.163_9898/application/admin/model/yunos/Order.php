<?php

namespace app\admin\model\yunos;

use think\Model;


class Order extends Model
{ 

    // 表名
    protected $name = 'order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'paytime_text',
        'exptime_text'
    ];
    

    
    public function getStatusList()
    {
        return ['10' => __('Status 10')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPaytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['paytime']) ? $data['paytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getExptimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['exptime']) ? $data['exptime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPaytimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setExptimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\Admin', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function access()
    {
        return $this->belongsTo('Access', 'access_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function account()
    {
        return $this->belongsTo('Account', 'account_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function orderlog()
    {
        return $this->hasMany('app\admin\model\yunos\OrderLog', 'orderid', 'orderid');
    }
    
    public function getNotifyInfoAttr($value,$data){
        
        return json_decode($value,true);
        
    }
    
    public function payAfter($order)
    {
        
        \app\admin\model\Admin::wallet($order);
        Account::change($order);
        Summary::change($order);
        
    }
}

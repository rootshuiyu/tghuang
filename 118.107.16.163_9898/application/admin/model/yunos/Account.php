<?php

namespace app\admin\model\yunos;

use think\Model;
use think\Db;


class Account extends Model
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

    public function access()
    {
        return $this->belongsTo('app\admin\model\Access', 'access_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function user()
    {
        return $this->belongsTo('app\admin\model\Admin', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function counts($account_id,$type)
    {
        $date = date($type);
        $sha1 = sha1($account_id.$date);
        $data = AccountCount::where('sha1',$sha1)->find();
        if($data){
            $result['in_number']   = $data['in_number'];
            $result['pull_number'] = $data['pull_number'];
            $result['day_fee']    = $this->totalfee(['account_id' => $account_id, 'date' => date('Ymd') ]);
            $result['terday_fee'] = $this->totalfee(['account_id' => $account_id, 'date' => date('Ymd') - 1]);
            $result['month_fee'] = $this->totalfee(['account_id' => $account_id,  'date' => date('Ym')]);
            return $result;
        }
        
        
        
        return [
            'day_fee' => 0,
            'terday_fee' => 0,
            'month_fee' => 0,
            'in_number' => 0,
            'pull_number' => 0,
            'fee' => 0,
        ];
    }
    
    public function totalfee($where)
    {
 
        $data = AccountCount::where($where)->value('fee');
        if(!$data){
            return 0;
        }
        return $data;
        
    }
    
    
    public function getConfigAttr($value,$data)
    {
        
        $result = json_decode($value,true);
        
        return $result;
        
    }
    
    public function getSconfigAttr($value,$data)
    {
        if(!$data['config']){
            return [];
        }
        
        $arr = json_decode($data['config'],true);
        $result = [];
        foreach ($arr as $v){
            $result[$v['key']] = $v['value'];
        }
        return $result;
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
                
                if($account->limit_day_number != 0){
                    $todayStart = date('Y-m-d 00:00:00');
                    $todayEnd   = date('Y-m-d 23:59:59');
                    $orderNumber = Order::where('account_id',$order->account_id)->whereTime('createtime', 'between', [$todayStart, $todayEnd])->count();
                    if($orderNumber >= $account->limit_day_number){
                        $account->switch = 0;
                        $account->runinfo   = '今日订单达到上限';
                    }
                }
                
                $account->save();
            }
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }
    
    public function RenderField($data){
        
        $list = [
            'account_name',
            'account_ck',
            'account_value1',
            'account_value2',
            'account_value3',
            'account_value4',
        ];
        
        foreach ($list as $k => $v){
            if($data['access'][$v]){
                $render = explode('|',$data['access'][$v]);
                if(!isset($render[2])){
                    $render[2] = '';
                }
                if(!isset($render[3])){
                    $render[3] = '';
                }
                $data['access'][$v] = $render;
            }
        }
        return $data;
        
    }
    
    public function autoIdentify($row,$params){

        $list = [
            'name',
            'ck',
            'value1',
            'value2',
            'value3',
            'value4',
        ];
        //$reader = new \yunos\QRCodeReader('.'. $params['value1'] );
        //$result = $reader->recognize();

        foreach ($list as $k => $value){
            
            $field = 'account_' . $value;
            
            $type = $row->access->$field ? $row->access->$field[1] : '';
            
            if($type == 'qrcode' && isset($params[$value])){
                
                if(!$params[$value]){
                    continue;
                }
                if (str_contains($params[$value], 'value')) {
                    continue;
                }
                
                $reader = new \yunos\QRCodeReader('.'. $params[$value] );
                $result = $reader->recognize();

                if($result){
                    $params[$value] .= '?value='. $result;
                }else{
                    throw new \Exception("二维码识别失败");
                }
            }

        }
        
        return $params;
        
    }
    
    
    
    
    
    
    
    
    
    
    
}

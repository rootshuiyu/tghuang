<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;
use app\api\traits\Pro;

class Account extends Model
{
    protected $name = 'account';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    protected $_error = null;
    protected $system = null;
    
    public function getConfigAttr($value,$data){
        
        return json_decode($value,true);
        
    }
    public function get_attr_user_ids($user_id)
    {
        
        $user = User::where('id',$user_id)->find();
        if(!$user || !$user['attr_user_ids']){
            return [];
        }

        $ids = explode(',',$user['attr_user_ids']);
        
        $result = User::where([
                'is_share' => 1,
                'switch'   => 1,
                'status'   => 'normal'
            ])->where('id','in',$ids)->column('id');
        return $result;
        
    }
    public function queryMay($user_id,$fee,$mid,$pay_type,$code)
    {
        $whereAccess['switch'] = 1;
        
        //$whereAccess['pay_type'] = ['like',"%$pay_type%"];
        
        if(!empty($code)){
           $whereAccess['code'] = $code;
        }
        
        $access_ids = Access::where($whereAccess)->where('pay_type','like','%'.$pay_type.'%')->column('id');
        if(!$access_ids){
            $this->_error = 'SUP暂无可用通道';
            return [];
        }
        
        $user_ids = User::where([
                'pid'    => $user_id,
                'switch' => 1,
                'status' => 'normal'
            ])->whereOr('id',$user_id)->column('id');
            
        
        $attr_user_ids = $this->get_attr_user_ids($user_id);
        
        if($attr_user_ids){
            
            $user_ids = array_unique(array_merge($user_ids, $attr_user_ids));
            
        }
        
        
        $where['switch']  = 1;
        //$where['access_id'] = ['in',$access_ids];
        
        if(!empty($mid)){
           $where['mid'] = $mid;
        }
        print_r("可用通道");
        print_r($access_ids);
        $result  = $this->where($where)
                    ->where('ck_pool',0)
                    ->where('access_id','in',$access_ids)
                    ->where(function ($query) use ($fee) {
                        $query->where('limit_min_fee', '<=', $fee)
                              ->whereOr('limit_min_fee', '=', 0);
                    })
                    ->where(function ($query) use ($fee) {
                        $query->where('limit_max_fee', '>=', $fee)
                              ->whereOr('limit_max_fee', '=', 0);
                    })
                    ->where(function ($query) use ($fee) {
                        $query->where('limit_fee', '>=', $fee)
                              ->whereOr('limit_fee', '=', 0);
                    })
                    ->where('user_id','in',$user_ids)
                    //->where('lock_time','<',date("Ymd"))
                    ->order('match_time')
                    ->order('active_time desc')
                    ->limit(1)
                    ->select();
                    
                    //->order('in_number desc')
                    //->orderRand()
                    
        if(!$result){
            $this->_error = 'SUP暂无可用账号';
            return [];
        }
        
        $center = $result;

        if(!$center){
            $this->_error = 'SUP暂无可用账号';
            return [];
        }
        
        print_r("匹配到可用账号\n");
        
        foreach ($center as $account){
            $account->match_time = time();
            $account->save();
        }
        
        return $center;
        
    }
    
    public function checkConfig($account)
    {
        
        /*if(!isset($account['ck']) ){
            $account->runinfo = '请检查CK,系统已关闭该账号';
            $account->switch = 0;
            $account->save();
            return false;
        }*/
        
        $config_tpl = $account->access->config_tpl;
        if(!$config_tpl){
            return true;
        }
        
        $result = true;
        foreach ($config_tpl as $v){
            
            if(empty($account->config[$v['key']])){
                $result = false;
                break;
            }
            
        }
        
        if($result){
           //$account->runinfo = '账号状态正常-'.date("Y-m-d H:i:s");
        }else{
           $account->runinfo = '请检查账号配置,系统已关闭该账号';
           $account->switch = 0;
            
        }
        
        $account->save();
        
        return $result;
        
    }
    
    public function access()
    {
        return $this->hasOne(Access::class,'id','access_id');
        
    }
    
    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
        
    }
    
    public function getError()
    {
        return $this->_error;
        
    }
    
    public static function change($order)
    {
        
        //记录入库
        Pro::model('AccountCount')->record($order->account_id,$order->fee);
        
        // 启动事务
        Db::startTrans();
        try {
            
            $account = self::where('id',$order->account_id)->find();
            if($account){
                
                if($account->limit_fee != 0){
                    $account->limit_fee -= $order->fee;
                }
                
                $account->runinfo   = '订单完成-'.$order->orderid;
                $account->in_fee    += $order->fee;
                $account->in_number +=  1;
                $account->save();
            }
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        
        
    }
    
    public function closeAccount($account_id,$run_info=''){
        
        // 启动事务
        Db::startTrans();
        try {
            
            $account = $this->where(['id' => $account_id ])->lock(true)->find();

            if($account){
                $account->runinfo = $run_info;
                $account->switch = 0;
                $account->save();
            }
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
    }
    
    public function online($account_id){
        
        $account = $this->where(['id' => $account_id ])->find();
        if(!$account){
            return;
        }
        $account->runinfo = '';
        $account->account_state = 1;
        $account->active_time = time();
        $account->save();
        
    }
    
    public function offline($account_id,$runinfo = ''){
        
        $account = $this->where(['id' => $account_id ])->find();
        if(!$account){
            return;
        }
        $account->switch  = 0;
        $account->runinfo = $runinfo;
        $account->account_state = 0;
        $account->active_time = time();
        $account->save();
        
    }
    
    public function setAccountData($account_id,$data){
        
        $account = $this->where(['id' => $account_id ])->find();
        if($account){
            foreach ($data as $k => $value){
                $account->$k = $value;
            }
            $account->save();
        }
        
    }
    
    public function addAccount($data = []){

        $sha1 = sha1($data['access_id'].$data['name']);
        if($this->where('sha1',$sha1)->find()){
            print_r('账号重复');
            return false;
        }
        print_r('正常上号');
        // 启动事务
        Db::startTrans();
        try {
            
            $data['mid'] = strtoupper(\fast\Random::alnum());
            $data['sha1'] = sha1($data['access_id'].$data['name']);
            $account = $this->save($data);
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        return true;
        
    }
    
    
    
}
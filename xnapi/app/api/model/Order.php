<?php

namespace app\api\model;

use think\Model;
use think\facade\Db;
use Bilulanlv\ThinkCache\facade\ThinkCache;
use app\api\traits\Pro;

class Order extends Model
{
    protected $name = 'order';
    protected $pk = 'id';
    protected $createTime = 'createtime';
    
    protected $timeout = 60;
    
    protected $_error = null;
    
    public function access()
    {
        return $this->hasOne(Access::class,'id','access_id');
        
    }
    
    public function account()
    {
        return $this->hasOne(Account::class,'id','account_id');
        
    }
    
    public function user()
    {
        return $this->hasOne(User::class,'id','admin_id');
        
    }
    
    public function getNotifyInfoAttr($value,$data){
        
        return json_decode($value,true);
        
    }
    
    public function setCache($order_id){
        
        $order = $this->where('orderid',$order_id)->find();
        ThinkCache::set('order_'.$order_id,$order,300);
        return $order;
        
    }
    
    public function getCache($order_id){
        
        $cache = ThinkCache::get('order_'.$order_id);
        if(!$cache){
            return $this->setCache($order_id);
        }
        
        return $cache;
        
    }
    
    public function match($order,$query,$compel = 1){

        $mayCenter = Pro::model('Account')->queryMay($query['user_id'],$query['fee'],$query['mid'],$query['pay_type'],$query['code']);
 
        Pro::logger('order')->resid($order['orderid'])->info('匹配到账号数量：'.count($mayCenter));
        
        $build = false;  $queueAccount = null;
        foreach ($mayCenter as $account){
            
            Pro::logger('order')->resid($order['orderid'])->info('正在执行下单流程，账号MID：'.$account['mid']);
            
            $build = $this->moduleInstance($account->access->module)
                          ->build($order,$account);
            if($build){
                //Pro::logger('order')->resid($order['orderid'])->success('接口下单成功');
                //Pro::model('AccountCount')->record($account['id']);
                //$account->runinfo = '匹配成功';
                //$account->save();
                break;
            }
            
            Pro::logger('order')->resid($order['orderid'])->info('下单流程执行完毕，账号MID：'.$account['mid']);
            
        }
        
        Pro::unlock('build_order_' . $order['orderid'] );

        if(!$build){

            Pro::logger('order')->resid($order['orderid'])->error('未匹配成功');
            \os\AsyncQueue::pushTask('OrderBuild', $order['orderid'],3);
            return;
        }
        
        $this->setCache($order['orderid']);
        
        Pro::logger('order')->resid($order['orderid'])->success('下单成功');
        
        \os\AsyncQueue::pushTask('AsyncOrderQuery', $order['orderid'],3);
        
    }
    
    public function limitApi($user,$params,$query){
        
        $count = $this->where(['status' => 0 , 'sup_user_id'=> $user->id,  'fee' => $params['fee']])->count();
        if($count > 100){
            return false;
        }
        return true;
        
    }
    
    public function start($user,$params,$query){
        
        if(!isset($params['suporder'])){
            $params['suporder'] = date("YmdHis").rand(100000,999999);
        }
        if(!isset($params['return_url'])){
            $params['return_url'] = '';
        }
        if(!isset($params['notify_url'])){
            $params['notify_url'] = '';
        }
        if(!isset($params['ip'])){
            $params['ip'] = '';
        }
        
        /*if(!$this->limitApi($user,$params,$query)){
            $this->_error = '当前未匹配完成订单过多，请稍后';
            return false;
        }*/
        
        $orderid = $this->getorderid();
        
        Pro::logger('order')->resid($orderid)->info('匹配条件',json_encode($query,256));
        
        $notify_info = [
            'appid' => $user['appid'],
            'appkey'=> $user['appkey'],
            'return_url' => $params['return_url'],
            'notify_url' => $params['notify_url'],
        ];
        $data = [
            'orderid'     => $orderid,
            'suporder'    => $params['suporder'],
            'user_id'     => $user->id,
            'sup_user_id' => $user->id,
            'status'      => 0,
            'fee'         => $query['fee'],
            'notify_info' => json_encode($notify_info),
            'exptime'     => time() + sconfig('timeout'),
            'sha1'        => sha1($user->appid . $params['suporder']),
            'query'       => json_encode($query,256),
            'pay_type'    => $query['pay_type'],
            'ip'          => $params['ip']
        ];
        
        Db::startTrans();
        try {
            $order = $this->where('sha1', $data['sha1'])->find();
            if (!$order) {
                $order = $this->create($data);
                Db::commit();
            }else{
                Db::rollback();
            }
        } catch (\Exception $e) {
            Db::rollback();
            Pro::console('错误: ' . $e->getMessage());
            return false;
        }
        
        $queue_id = \os\AsyncQueue::pushTask('OrderBuild', $order['orderid'],1);
        
        Pro::logger('order')->resid($order['orderid'])->info("队列ID已推送:$queue_id");
        
        return $order; // 统一返回
        
        
    }
    
    public function moduleInstance($className) {
        
        // 构建动态类路径
        $moduleNamespace = '\app\api\module';
        $fullClassName = $moduleNamespace . '\\' . $className;
        $moduleInstance = new $fullClassName();
        
        
        return $moduleInstance;
        
    }
    
    public function getorderid() {
        
        $uuid = $this->generateUUID();
        return $this->uuidToOrderNumber($uuid);
        
    }
    
    public function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    public function uuidToOrderNumber($uuid) {
        return "XN" . str_replace('-', '', $uuid);
    }
    
    public function exOrder($order_id,$account,$payurl,$syorder = null,$extend = []){
        
        $result = false;
        Db::startTrans();
        try {

            $order = $this->where(['orderid' => $order_id ,'status' => 0])->lock(true)->find();
            
                if($order){
                    $order->syorder    = $syorder;
                    $order->payurl     = json_encode($payurl);
                    //$order->pay_type   = $account->access->pay_type;
                    $order->account_id = $account->id;
                    $order->access_id  = $account->access->id;
                    $order->mid        = $account->mid;
                    $order->user_id    = $account->user_id;
                    $order->status     = 2;
                    $order->exptime    =  time() + $account->access->timeout;
                    $order->match_success_time = time();
                    foreach ($extend as $k => $v){
                        $order->$k = $v;
                    }
                    $order->save();
                    $result = true;
                    // 提交事务
                    Db::commit();
                    
                }else{
                    Db::rollback();
                }

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        if(!$result){
            Pro::logger('order')->resid($order_id)->error('数据库事务错误');
        }
        
        return $result;
        
    }
    
    public function exOrderunLock($order_id,$account,$payurl,$syorder = null,$extend = []){
        
        $result = false;
        try {

            $order = $this->where(['orderid' => $order_id ,'status' => 0])->lock(true)->find();
            
                if($order){
                    $order->syorder    = $syorder;
                    $order->payurl     = $payurl;
                    $order->pay_type   = $account->access->pay_type;
                    $order->account_id = $account->id;
                    $order->access_id  = $account->access->id;
                    $order->mid        = $account->mid;
                    $order->user_id    = $account->user_id;
                    $order->status     = 2;
                    $order->exptime    =  time() + $account->access->timeout;
                    $order->match_success_time = time();
                    foreach ($extend as $k => $v){
                        $order->$k = $v;
                    }
                    $order->save();
                    $result = true;
                }

        } catch (\Exception $e) {
            Pro::logger('order')->resid($order_id)->error('数据库事务错误');
        }
        
        return $result;
        
    }
    
    public function settle($orderid) {
        $result = false;
        
        // 先不加锁查询
        $order = $this->where(['orderid' => $orderid])->find();
        if (!$order) return false;
    
        // 状态10的处理不需要事务
        if ($order->status == 10) {
            \os\AsyncQueue::pushTask('OrderNotify', $order['orderid']);
            return true;
        }

        // 状态2的处理需要事务
        if ($order->status == 2) {
            Db::startTrans();
            try {
                // 重新加锁查询确保数据最新
                $order = $this->where([
                    'orderid' => $orderid,
                    'status' => 2 // 乐观锁
                ])->lock(true)->find();
                
                if ($order) {
                    $order->status = 10;
                    $order->paytime = time();
                    $order->hash = null;
                    $order->save();
                    Db::commit();
                    
                    // 事务提交后再处理后续逻辑
                    $this->payAfter($order);
                    \os\AsyncQueue::pushTask('OrderNotify', $order['orderid']);
                    
                    $result = true;
                } else {
                    Db::commit(); // 查询无结果也要提交
                }
            } catch (\Exception $e) {
                Db::rollback();
                throw $e; // 建议抛出或记录日志
            }
        }
        
        return $result;
    }
    
    public function callbackHttp($orderid) {
        $result = false;
        
        // 先不加锁查询
        $order = $this->where(['orderid' => $orderid])->find();
        if (!$order) return false;
        
        Pro::model('OrderLog')->msg($order['orderid'],'info','手动回调');
    
        // 状态10的处理不需要事务
        if ($order->status == 10) {
            \os\AsyncQueue::pushTask('OrderNotify', $order['orderid']);
            return true;
        }
        
        // 状态2的处理需要事务
        if ($order->status == 2 || $order->status == 3) {
            Db::startTrans();
            try {
                // 重新加锁查询确保数据最新
                $order = $this->where([
                    'orderid' => $orderid,
                    'status' => $order->status // 乐观锁
                ])->lock(true)->find();
                
                if ($order) {
                    $order->status = 10;
                    $order->paytime = time();
                    $order->hash = null;
                    $order->save();
                    Db::commit();
                    
                    // 事务提交后再处理后续逻辑
                    $this->payAfter($order);
                    \os\AsyncQueue::pushTask('OrderNotify', $order['orderid']);
                    $result = true;
                } else {
                    Db::commit(); // 查询无结果也要提交
                }
            } catch (\Exception $e) {
                Db::rollback();
                throw $e; // 建议抛出或记录日志
            }
        }
        
        return $result;
    }
    
    public function settleSyorder($syorder){
        
        // 启动事务
        Db::startTrans();
        try {
            
            $order = $this->where(['syorder' => $syorder, 'status' => 2])->lock(true)->find();

            if($order){
                $order->status = 10;
                $order->paytime = time();
                $order->hash = null;
                $order->save();
                // 提交事务
                Db::commit();
                $this->payAfter($order);
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
    }
    
    public function voided($orderid,$error_info=''){
        
        // 启动事务
        Db::startTrans();
        try {
            
            $order = $this->where(['orderid' => $orderid ])->lock(true)->find();

            if($order){
                $order->status     = 3;
                $order->taking     = 0;
                $order->hash       = null;
                $order->run_info   = $error_info;
                $order->error_info = $error_info;
                $order->save();
            }
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
    }
    
    public function payAfter($order){
        
        ThinkCache::delete('order_success_cache');
        
        $this->setCache($order['orderid']);
        
        //Pro::model('OrderLog')->msg($order['orderid'],'success','订单完成');
        
        //\app\queue\yunos\Notify::createQueueProducer()->scheduleDelayedMessage($order['orderid'], 1);
        \os\AsyncQueue::pushTask('OrderNotify', $order['orderid']);
        
        User::wallet($order);
        Account::change($order);
        Summary::change($order);
        
    }
    
    public function getError()
    {
        return $this->_error;
        
    }
    
    public function setOrderData($orderid,$data = []){

        // 启动事务
        Db::startTrans();
        try {

            $order = $this->where(['orderid' => $orderid ])->lock(true)->find();

            if($order){
                foreach ($data as $k => $value){
                    $order->$k = $value;
                }
                $order->save();
                // 提交事务
                Db::commit();
            }else{
                // 回滚事务
                Db::rollback();
            }
            
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
    }
    
    public function locks($where,$data = []){
        
        $isData = $this->where($where)->find();
        if(!$isData){
            return [];
        }
        $result = [];
        // 启动事务
        Db::startTrans();
        try {

            $sql = $this->where($where)->lock(true)->find();

            if($sql){
                foreach ($data as $k => $value){
                    $sql->$k = $value;
                }
                $sql->save();
                $result = $sql;
            }
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        return $result;
        
    }

    public function sss($order){
        
        //Summary::change($order);
        
    }
    
    
    
}
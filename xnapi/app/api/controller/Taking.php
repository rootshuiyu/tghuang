<?php
namespace app\api\controller;

use Webman\RedisQueue\Client;
use support\Request;
use think\facade\Db;

class Taking extends Api
{
    
    protected $noNeedLogin = ['index'];
    protected $order   = null;
    protected $card    = null;
    
    public function __construct(Request $request = null)
    {
        parent::__construct();
        $this->order    = new \app\api\model\Order;
        $this->card     = new \app\api\model\Card;
        
    }
    
    public function miaoapp_get(Request $request)
    {
        $key      = $request->get('key');
        if($key != '97d758108c5dbc9cb16ab4cb4400416b247114d5'){
            $this->error(1);
        }
        
        Db::startTrans();
        try {
            
           $order = $this->order->where(['status' => 0 ,'taking' => 1 ,'access_id' => 5])->lock(true)->find();
           if(!$order){
              $this->error(2);
           }
           
           $order->taking = 2;
           $order->save();
            
            
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error(3);
        }
        
        //$ck = queryck($order['account']['ck']);
        
        $result = [
            'orderid' => $order['orderid'],
            'ck'      => base64_encode($order['account']['ck']),
            'fee'     => $order['fee'],
            //'sup_qq'  => $order['account']['config']['sup_qq']
        ];
        return $this->success('ok',$result);
        
    }
    
    public function miaoapp_ok(Request $request)
    {
        $key       = $request->get('key');
        $orderid   = $request->post('orderid');
        $content   = $request->post('content');
        $syorder   = $request->post('syorder');
        
        if(!$key || !$orderid || !$content || !$syorder){
            $this->error(1);
        }
        
        if($key != '97d758108c5dbc9cb16ab4cb4400416b247114d5'){
            $this->error(2);
        }
        
        Db::startTrans();
        try {
           
           $order = $this->order->where(['orderid' => $orderid,'status' => 0 ,'taking' => 2])->lock(true)->find();
           if(!$order){
              $this->error();
           }
           
           $order->status  = 2;
           $order->taking  = 10;
           $order->syorder = $syorder;
           $order->payurl  = $content;
           $order->exptime =  time() + $order->access->timeout;
           $order->save();
            
            
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error(3);
        }
        
        return $this->success('ok');
        
    }
    
    public function miaoapp_error(Request $request)
    {
        
        $key       = $request->get('key');
        $orderid   = $request->post('orderid');
        
        if($key != '97d758108c5dbc9cb16ab4cb4400416b247114d5'){
            $this->error(2);
        }
        
        if(!$orderid){
            $this->error();
        }
        Db::startTrans();
        try {
           
           $order = $this->order->where(['orderid' => $orderid,'status' => 0])->lock(true)->find();
           if(!$order){
              $this->error();
           }
           
           $order->status  = 1;
           $order->save();
           
           $order->account->runinfo = '账号下单失败';
           $order->account->switch = 0;
           $order->account->save();
            
            
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error(3);
        }
        
        $this->success();
    }
    
    public function bbb(Request $request)
    {
        
        $order = $this->order->where(['orderid' => 'KM13cf2616823640fca69dffad2728efd6'])->find();
        return $order->access->timeout;
        
    }
    
    public function sss(Request $request)
    {
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd   = date('Y-m-d 23:59:59');
        $orderNumber = $this->order->whereTime('createtime', 'between', [$todayStart, $todayEnd])->count();
        return $orderNumber;
        
        $params = [
                'url' => 'https://m.jiaoyimao.com/order/detail/7114884941994784',
                'header' => ':authority: m.jiaoyimao.com
:method: GET
:path: /order/detail/7114884941994784
:scheme: https
accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9
accept-language: zh-CN,zh;q=0.9
cookie: ssids=2791736956267550; sk_tower_uuid=3683e0cc-becf-4096-86be-abcd67d5c276; _bl_uid=20mh05kkyn62Rnwmvx0mijXemh2m; cna=HsQOIEEavjUCAavfoGohM/Wm; track_id=gcmall_1736775374596_66dedc60-a729-403c-8f99-b32e5cc0c696; ieu_member_biz_id=jiaoyimao; ieu_member_biz_id.sig=IpsjlCZxHMt7w1cZ1rcO2tBOsJL5ouVK03I7A1ynMKE; ieu_member_sid=pt112185czn0395635wb6qzf96j0i8a6w5as6928tl; ieu_member_sid.sig=KCv9UTkwpnaLMnrWbzcdrpAuLX3Ev7FArwH5EQBiyWk; ieu_member_appcode=JYM_H5; ieu_member_appcode.sig=1N8cgNLOZxnWzKkIynis5xTxIaRjGDw-6dlZEMZglE8; ieu_member_passport_id=2881736775408351532; ieu_member_passport_id.sig=m61LnPXlPsEQCia76M9GTvzaVBi0SntevR-YfQKV5x0; ieu_member_uid=1736775408555777; ieu_member_uid.sig=sGub9Wn2pz6UtNdDguY35WwVS8sSsiL9ugcYOqBgK2w; jym_session_id=pt112185czn0395635wb6qzf96j0i8a6w5as6928tl; jym_session_id.sig=v2pCu9szKP5EqUwZMEdcewpoSW_hn8PK_WgH_AqNbww; xlly_s=1; ssids=2791736956267550; logTraceJymId=4f765487950e-44a8-9610-3395; JYM_FIRST_GAME_SWITCH_HISTORY=%7B%22gameId%22%3A%221002480%22%2C%22gameName%22%3A%22%E4%B8%89%E5%9B%BD%E6%9D%80%22%7D; t=7900ec3f72d692ccd224523e9ea8b63e; EGG_SESS=3v6DgAB-jKbLOzN5Qa1pFi5yHoQKYp1Php5wCXl9Vy7Ix_eco_QOS_jC-0TzxDVnQlyX2Go5xP6xCzSgyZ5jFML6Fcw7WrLsaXsf05qoMbtsckIhp2pDdaWgJl89cIDU; ctoken=b_QZVdc1ySqpm70XlbDfXyxn; aplus_ieu_ch=10438; Hm_lvt_47366dcc92e834539e7e9c3dcc2441de=1736862993,1737029027; HMACCOUNT=2160BE5F30B36019; _m_h5_tk=27395062df7d491c9d5db015220810c6_1737036227540; _m_h5_tk_enc=8933127d222fe5df9381ea7601e0e6fb; web_entry_type=normal; aplus_ieu_ch=10438; isg=BD09yc5qBtQd8KK1eeWNZ9tOTJk32nEshwBTzf-CeBTDNl9oxysR_zzl5Gxwtonk; out_member_umidToken=T2gAvNnJbSjAgu3MuxLQBUXcPKqyceVzpajV229VBEV0Fd9A_eiLXLxyiDf9uEXTNDs=; member_umidToken=T2gAvNnJbSjAgu3MuxLQBUXcPKqyceVzpajV229VBEV0Fd9A_eiLXLxyiDf9uEXTNDs=; Hm_lpvt_47366dcc92e834539e7e9c3dcc2441de=1737029201; tfstk=gZJjncsDWr4ffW0MZEmPRt5DNqB1h0keBls9xhe4XtBvCQTXlmb4gIJ1CUQy3Z-VHF19-esNkFRVWGQJ7Srqm1rDZe-L3KlcQR6cIO3E8vke0nXNB5TxPye0eGK8WNhPM4OXqOuE8vkr8aOad282duJIPGbRDiBOk01RvMWTMSLOy_IdYrB9WOn5eMsUWoBO6TIRfaQOBOL920sNyiHtHXjuGwxjc40WSfuZRtb7BRp522_pcwslYLsfGtCRPRI2Fi1fJnJ2dQY52LJ5T_rZ_TKHaEI5dAwRh69Dd_pxrR1k5aCMH3uQtwdJuFC6Z2wFctR5NgOIYJ1N3p5dHQloy1WfAp6JpV2CAt7c3_psyAtlUMfeHOqaQ36Ax69cNle6J3-en_vsRzQMgMXRcFHKPgrbLwsbd5NGkRs580i7s54dfm40hHONMsIl43nSV55GMgj580i7s5fAqg--V0NNs',
                'body' => '',
                'orderid' => 7114884941994784,
        ];

        $http = http_get_d($params);

        $pattern = '/<div class="title">(.*?)<\/div>/s';
 
        // 执行匹配
        if (!preg_match_all($pattern, $http, $matches)) {
            //return 'error';
        }
        if($matches[1][1] == '待收货'){
            
        }
        return json($matches[1][1]);
        
    }
    
    public function pick_miaoapp_card(Request $request)
    {
        //13有问题
        /*$order = $this->order->where([
               'status'    => 10 ,
               'taking'    => 10 ,
               'access_id' => 5
               ])->whereTime('createtime', '>=', '2025-1-30')->count(); 
        return $order;*/
        Db::startTrans();
        try {
           
           $order = $this->order->where([
               'status'    => 10 ,
               'taking'    => 10 ,
               'access_id' => 5
               ])->whereTime('createtime', '>=', '2025-1-30')->lock(true)->find();
           if($order){
               $order->taking = 11;
               $order->save();
           }
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        if(!$order){
            return json([123]);
        }
        
        $cookie = $order['account']['ck'];
        
        $cookie = str_replace('.jiaoyimao.com', "", $cookie);
        $cookie = str_replace('domain:', "", $cookie);
        $cookie = str_replace('path:', "", $cookie);
        $cookie = str_replace('name:', "", $cookie);
        $cookie = str_replace('value:', "=", $cookie);
        $cookie = str_replace('/*/*-*', ";", $cookie);
        
        $result = [
            'orderid' => $order['orderid'],
            'syorder' => $order['syorder'],
            'ck'      => queryck($cookie),
            //'sup_qq'  => $order['account']['sup_qq']
        ];
        return json($result);
        
    }
    
    public function push_miaoapp_card(Request $request)
    {
        //return;
        $orderid   = $request->post('orderid');
        $value1    = $request->post('value1');
        $value2    = $request->post('value2');
        $status    = $request->post('status',0);
        if(!$orderid || !$value1 || !$value2){
            $this->success();
        }
        
        Db::startTrans();
        try {
           
           $order = $this->order->where([
               'orderid'   => $orderid,
               'status'    => 10 ,
               'taking'    => 11 ,
               ])->lock(true)->find();
           if($order){
            $card = $this->card->where('orderid',$orderid)->find();
            if(!$card && $status == 1){
                $data = [
                    'orderid'    => $orderid ,
                    'user_id'    => $order->user_id,
                    'account_id' => $order->account_id,
                    'mid'        => $order->mid,
                    'value1'     => $value1,
                    'value2'     => $value2,
                    'fee'        => $order->fee
                ];
                $this->card->create($data);
            }
           
            $order->taking = 12;
            $order->save();
           }
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        $this->success();
        
        
    }
    
    public function pick_miaoapp_count(Request $request)
    {
        
        //13有问题
        $order = $this->order->where([
               'status'    => 10 ,
               'taking'    => 11 ,
               'access_id' => 5
               ])->whereTime('createtime', '>=', '2025-1-30');
        return $order;
        
    }
    
    public function miaoappqb_get(Request $request)
    {
        $key      = $request->get('key');
        if($key != '97d758108c5dbc9cb16ab4cb4400416b247114d5'){
            $this->error(1);
        }
        
        Db::startTrans();
        try {
            
           $order = $this->order->where(['status' => 0 ,'taking' => 1,'access_id' => 6])->lock(true)->find();
           if(!$order){
              $this->error(2);
           }
           
           $order->taking = 2;
           $order->save();
            
            
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error(3);
        }
        
        //$ck = queryck($order['account']['ck']);
        
        $result = [
            'orderid' => $order['orderid'],
            'ck'      => base64_encode($order['account']['ck']),
            'fee'     => $order['fee'],
            'sup_qq'  => $order['account']['config']['sup_qq']
        ];
        return $this->success('ok',$result);
        
    }
    
    public function miaoappqb_ok(Request $request)
    {
        $key       = $request->get('key');
        $orderid   = $request->post('orderid');
        $content   = $request->post('content');
        $syorder   = $request->post('syorder');
        
        if(!$key || !$orderid || !$content || !$syorder){
            $this->error(1);
        }
        
        if($key != '97d758108c5dbc9cb16ab4cb4400416b247114d5'){
            $this->error(2);
        }
        
        Db::startTrans();
        try {
           
           $order = $this->order->where(['orderid' => $orderid,'status' => 0 ,'taking' => 2])->lock(true)->find();
           if(!$order){
              $this->error();
           }
           
           $order->status  = 2;
           $order->taking  = 10;
           $order->syorder = $syorder;
           $order->payurl  = $content;
           $order->exptime =  time() + $order->access->timeout;
           $order->save();
            
            
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error(3);
        }
        
        return $this->success('ok');
        
    }
    
    public function miaoappqb_error(Request $request)
    {
        
        $key       = $request->get('key');
        $orderid   = $request->post('orderid');
        
        if($key != '97d758108c5dbc9cb16ab4cb4400416b247114d5'){
            $this->error(2);
        }
        
        if(!$orderid){
            $this->error();
        }
        Db::startTrans();
        try {
           
           $order = $this->order->where(['orderid' => $orderid,'status' => 0])->lock(true)->find();
           if(!$order){
              $this->error();
           }
           
           $order->status  = 1;
           $order->save();
           
           $order->account->runinfo = '账号下单失败';
           $order->account->switch = 0;
           $order->account->save();
            
            
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error(3);
        }
        
        $this->success();
    }
    
}
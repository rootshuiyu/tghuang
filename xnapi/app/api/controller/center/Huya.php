<?php
namespace app\api\controller\center;

use Webman\RedisQueue\Client;
use support\Request;
use think\facade\Db;
use Zxing\QrReader;

class Huya
{
    
    protected $order   = null;
    protected $card    = null;
    protected $_error  = null;
    
    public function __construct(Request $request = null)
    {
        $this->order    = new \app\api\model\Order;
        $this->card     = new \app\api\model\Card;
        
    }
    
    public function get($request)
    {
        
        $result = false;
        Db::startTrans();
        try {
           
           $order = $this->order->where(['status' => 0 ,'taking' => 1 ,'access_id' => 9])->lock(true)->find();
           if($order){
               $result = [
                    'orderid' => $order['orderid'],
                    'fee'     => $order['fee'],
                    'ck'      => $order['fee'],base64_encode($order['account']['ck']),
                ];
                $order->taking = 2;
                $order->save();
           }
            
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            
        }
        
        if(!$result){
            $this->_error = '暂无订单';
        }
        
        return $result;
        
    }
    
    public function ok($request)
    {
        
        /*$imageData = file_get_contents($request['qr_url']);
        $filePath = 'runtime/'.time().'-' . rand(100000,999999) . '.png';
        if(!file_put_contents($filePath, $imageData)){
            return false;
        };
        
        $qrcode = new QrReader($filePath);
        $text = $qrcode->text();
        if(!$text){
            return false;
        }*/
        //unlink($filePath);
        $payurl  = 'http://paygate.huya.com:80/payUrl/';
        $payurl .= substr(strrchr($request['qr_url'], '/'), 1);
        
        $result = false;
        Db::startTrans();
        try {
           
           $order = $this->order->where(['orderid' => $request['orderid'] ,'status' => 0 ,'taking' => 2])->lock(true)->find();
           if($order){
              $order->status  = 2;
               $order->taking  = 10;
               //$order->syorder = $syorder;
               $order->payurl  =  $payurl;
               $order->exptime =  time() + $order->access->timeout + 60;
               $order->save();
               $result = true;
           }
            
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
        return $order;
        
    }
    
    public function set_order($request)
    {
        
        Db::startTrans();
        try {
           
           $order = $this->order->where(['orderid' => $request['orderid'] ,'status' => 0])->lock(true)->find();
           if($order){
              $order->status  = 1;
              $order->runinfo = '接口取消';
              $order->save();
           }
            
        Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        
    }
    
    
    public function getError(){
        return $this->_error;
    }
    
    
}
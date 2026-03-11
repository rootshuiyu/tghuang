<?php
namespace app\process;

use Workerman\Timer;
use app\api\traits\Pro;

class OrderExpired
{
    
    public function onWorkerStart()
    {
        
        Timer::add(6, function () {
            $this->timeOut();
        });

    }
    
    public function timeOut()
    {
        
        $lock = Pro::exlock('delele_order');
        if($lock){
            print_r("等待清理---\n");
            return;
        }
        
        $model  = Pro::model('Order');
        
        $timeout = time() - 3;
        $model->where('status',0)->where('exptime', '<', $timeout)->limit(50)->update(['status' => 1]);
        
        $model->where('status',2)->where('exptime', '<', $timeout)->limit(50)->update(['status' => 3 ,'hash' => null]);
        
        Pro::unlock('delele_order');
        
    }
}
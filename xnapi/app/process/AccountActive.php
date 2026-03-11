<?php
namespace app\process;

use Workerman\Timer;
use app\api\traits\Pro;

class AccountActive
{
    
    public function onWorkerStart()
    {

        Timer::add(5, function () {
            Pro::console('开始活跃账号');
            $this->exTask();
        });

    }
    
    public function exTask()
    {
        
        $list = Pro::model('Access')->where(['switch' => 1,'active_state' => 1])->select();
        
        foreach ($list as $access){
            
            Pro::module($access['module'])->activeAccount($access);
            
        }
        
    }
    
}
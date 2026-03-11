<?php
namespace app\process;

use Workerman\Timer;
use app\api\traits\Pro;

class OrderQuery
{
    
    public function onWorkerStart()
    {
        
        Timer::add(5, function () {
            $this->query();
        });

    }
    
    public function query()
    {

        $lock = Pro::exlock('query_order',20);
        if($lock){

            return;
        }
        
        // 分页处理订单
        $page = 1;
        $pageSize = 100;
        do {
            $longTime = time() - 10 ;
            $data = Pro::model('Order')
                ->where('status', 2)
                ->where('query_time', '<', time())
                ->page($page, $pageSize)
                ->select();
                
            if(empty($data)) {
                break;
            }
            
            // 批量处理订单
            foreach ($data as $order) {
                $app = Pro::module($order['access']['module']);
                $app->queryOrder($order);
                Pro::console("转移查询".$order['orderid']);
            }
            
            $page++;
            
            // 每处理完一页释放锁并重新获取，避免长时间占用
            Pro::unlock('query_order');
            if(!$lock = Pro::exlock('query_order', 20)) {

                return;
            }
            
        } while(count($data) == $pageSize);
        
        Pro::unlock('query_order');
        
    }
}
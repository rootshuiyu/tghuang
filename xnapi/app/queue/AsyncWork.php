<?php
namespace app\queue;

use os\AsyncQueue;
use app\api\traits\Pro;

class AsyncWork
{
    use \app\queue\QueueTrait;
    //\os\AsyncQueue::pushTask('usera', ['foo' => 'bar'],3);
    protected $mode = 'compete'; // 或 'compete'/broadcast
    protected $task = 'AsyncWork';      // 业务/任务名
    
    // 重试配置
    protected $maxRetries = 3;     // 最大重试次数
    protected $retryInterval = 5;  // 重试间隔（秒）
    
    public function ok($data)
    {
        $parame = $data['data'];
        
        if(!isset($parame['app']) || !isset($parame['action'])){
            return;
        }
        $acion = $parame['action'];
        
        $Instantiate = Pro::module($parame['app']);
        
        $Instantiate->$acion($parame['data']);
        
    }
}
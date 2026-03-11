<?php
namespace app\queue;

use os\AsyncQueue;
use os\AsyncTimer;

class DelayDispatcher
{
    use \app\queue\QueueTrait;
    //\os\AsyncQueue::pushTask('usera', ['foo' => 'bar'],3);
    protected $mode = 'compete'; // 或 'compete'/broadcast
    protected $task = 'DelayDispatcher';      // 业务/任务名
    
    // 重试配置
    protected $maxRetries = 3;     // 最大重试次数
    protected $retryInterval = 5;  // 重试间隔（秒）
    
    public function ok($data)
    {
        // 添加详细的调试信息
        print_r("DelayDispatcher: 收到任务\n");
        $task = $data['data'];
        print_r($task);
        
        $methodValue  = $task['methodValue'];
        $calledMethod = $task['calledMethod'];
        $calledParams = $task['calledParams'];
        //Pro::$methodValue($task['className'])->$calledMethod(2);
        
        $instance = call_user_func_array(['\app\api\traits\Pro', $methodValue], [$task['className']]);
        call_user_func_array([$instance, $calledMethod], $calledParams);
        
        
    }
}
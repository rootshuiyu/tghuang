<?php
namespace os;
use app\api\traits\Pro;
/**
 * AsyncTimer类 - 提供类似Workerman\Timer的延迟执行功能，同时集成到现有异步队列系统中
 * 支持链式调用，如AsyncTimer::delay(5)->call([$obj, 'method'], $arg1, $arg2);
 */
class AsyncTimer
{
    /**
     * 定时器是否已经启动
     * @var bool
     */
    protected static $timerStarted = false;

    /**
     * 启动定时器来处理延迟任务（应该在应用启动时调用一次）
     * 在webman框架中，可以在workerStart事件中调用
     */
    public static function startDelayTimer()
    {
        if (self::$timerStarted) {
            return; // 已经启动过了
        }
        
        self::$timerStarted = true;
        
        // 检查是否在CLI环境中运行（Workerman通常在CLI环境运行）
        if (php_sapi_name() === 'cli') {
            // 创建一个后台进程来定期处理延迟任务
            $pid = pcntl_fork();
            
            if ($pid === -1) {
                //  fork失败
                error_log('AsyncTimer: 无法创建后台进程来处理延迟任务');
            } elseif ($pid === 0) {
                // 子进程
                register_shutdown_function(function() {
                    // 子进程退出时的清理工作
                });
                
                echo "AsyncTimer: 启动延迟任务处理器...\n";
                
                // 每1秒检查一次延迟任务
                while (true) {
                    try {
                        // 处理DelayDispatcher队列中的延迟任务
                        AsyncQueue::handleDelayByTask('DelayDispatcher');
                    } catch (\Throwable $e) {
                        error_log('AsyncTimer: 处理延迟任务时出错: ' . $e->getMessage());
                    }
                    
                    // 休眠1秒
                    sleep(1);
                }
            }
            // 父进程继续执行
        } else {
            // 在Web环境中，可以建议用户在适当的地方手动调用handleDelayByTask
            error_log('AsyncTimer: 建议在CLI环境中运行以启用自动延迟任务处理');
        }
    }

    /**
     * 延迟时间（秒）
     * @var int
     */
    protected $delay = 0;
    
    /**
     * 单例实例
     * @var AsyncTimer
     */
    protected static $instance = null;
    
    /**
     * 私有构造函数，防止直接实例化
     */
    private function __construct() {}
    
    /**
     * 获取AsyncTimer实例
     * @return AsyncTimer
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 设置延迟执行时间
     * @param int $seconds 延迟秒数
     * @return AsyncTimer
     */
    public static function delay(int $seconds)
    {
        $instance = self::instance();
        $instance->delay = $seconds;
        return $instance;
    }
    
    /**
     * 立即执行（无延迟）
     * @return AsyncTimer
     */
    public static function now()
    {
        return self::delay(0);
    }
    
    /**
     * 调用指定的回调函数，支持数组回调（如[$obj, 'method']）、字符串回调（如'Class::method'）和闭包函数
     * @param callable|string|array $callback 回调函数
     * @param mixed ...$args 额外参数
     * @return bool 投递是否成功
     */
    public function call($callback, ...$args)
    {
        // 包装任务数据
        $taskData = [
            'callback' => $callback,
            'args' => $args
        ];
        
        // 使用现有的AsyncQueue投递任务到DelayDispatcher任务队列
        return AsyncQueue::pushTask('DelayDispatcher', $taskData, $this->delay);
    }
    
    /**
     * 执行指定的类方法
     * @param string $className 类名
     * @param string $methodName 方法名
     * @param mixed ...$args 方法参数
     * @return bool 投递是否成功
     */
    public function exec(string $className, string $methodName, ...$args)
    {
        return $this->call([$className, $methodName], ...$args);
    }
    
    /**
     * 执行静态方法
     * @param string $staticMethod 静态方法，格式为'Class::method'
     * @param mixed ...$args 方法参数
     * @return bool 投递是否成功
     */
    public function staticCall(string $staticMethod, ...$args)
    {
        return $this->call($staticMethod, ...$args);
    }
    
    /**
     * 处理队列中的任务（由DelayDispatcher调用）
     * @param array $taskData 任务数据
     * @return mixed
     */
    public static function handleTask(array $taskData)
    {
        $callback = $taskData['callback'];
        $args = $taskData['args'] ?? [];
        
        try {
            // 处理不同类型的回调
            if (is_callable($callback)) {
                // 支持闭包函数，包括包含链式调用的闭包
                return call_user_func_array($callback, $args);
            } elseif (is_string($callback) && strpos($callback, '::') !== false) {
                // 处理静态方法调用，如 'Class::method'
                list($className, $methodName) = explode('::', $callback, 2);
                if (class_exists($className) && method_exists($className, $methodName)) {
                    return call_user_func_array([$className, $methodName], $args);
                }
            } elseif (is_array($callback) && count($callback) === 2) {
                // 处理对象方法调用，如 [$obj, 'method'] 或 [ClassName::class, 'method']
                list($class, $method) = $callback;
                if (is_object($class)) {
                    if (method_exists($class, $method)) {
                        return call_user_func_array([$class, $method], $args);
                    }
                } elseif (is_string($class) && class_exists($class) && method_exists($class, $method)) {
                    return call_user_func_array([new $class(), $method], $args);
                }
            }
            
            // 如果回调不可调用，记录错误
            error_log('AsyncTimer: 无效的回调: ' . var_export($callback, true));
            return false;
        } catch (\Throwable $e) {
            // 捕获所有异常，记录错误
            error_log('AsyncTimer: 执行任务时出错: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * 为了兼容用户期望的 Pro::moduled('asdasd')->aaa($aaa) 调用方式，
     * 我们创建一个静态包装方法，使用__callStatic魔术方法来处理链式调用
     * 
     * 示例：AsyncTimer::proxy('Pro')->moduled('asdasd')->aaa($aaa);
     * 
     * @param string $className 要代理的类名
     * @return AsyncProxy
     */
    public static function proxy(string $className)
    {
        return new AsyncProxy($className);
    }
}

/**
 * AsyncProxy类 - 用于代理链式调用，将其转换为异步任务
 */
class AsyncProxy
{
    /**
     * 代理的类名
     * @var string
     */
    protected $className;
    
    /**
     * 方法调用链
     * @var array
     */
    protected $callChain = [];
    
    /**
     * 延迟时间
     * @var int
     */
    protected $delay = 0;
    
    /**
     * 构造函数
     * @param string $className 要代理的类名
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }
    
    /**
     * 设置延迟时间
     * @param int $seconds 延迟秒数
     * @return AsyncProxy|bool 如果是链式调用的最后一步，返回投递结果；否则返回$this以继续链式调用
     * 
     * 注意：当以函数调用方式使用（不以变量接收返回值）时，会在方法结束时自动触发异步执行
     * 例如：AsyncTimer::proxy('Pro')->moduled('asdasd')->aaa($aaa)->delay(5);
     * 当以变量接收返回值时，需要手动调用async()：
     * $proxy = AsyncTimer::proxy('Pro')->moduled('asdasd')->aaa($aaa)->delay(5);
     * $proxy->async();
     */
    public function delay(int $seconds)
    {
        $this->delay = $seconds;
        
        // 使用debug_backtrace检查调用上下文，判断是否是链式调用的最后一步
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (isset($backtrace[1]) && strpos($backtrace[1]['function'], '__') === 0) {
            // 前面有魔术方法调用，说明是链式调用中间步骤，返回$this
            return $this;
        } else {
            // 是链式调用的最后一步，自动触发异步执行
            return $this->async();
        }
    }
    
    /**
     * 魔术方法，捕获所有方法调用并记录到调用链中
     * @param string $method 方法名
     * @param array $args 方法参数
     * @return AsyncProxy
     */
    public function __call($method, $args)
    {
        $this->callChain[] = [$method, $args];
        return $this;
    }
    
    /**
     * 执行异步调用链
     * @return bool 投递是否成功
     */
    public function async()
    {
        // 添加调试信息，确认任务被投递
        print_r("\n=== AsyncProxy::async 被调用 ===\n");
        print_r("准备投递异步任务: \n");
        print_r("类名: " . $this->className . "\n");
        print_r("调用链: " . json_encode($this->callChain) . "\n");
        print_r("延迟时间: " . $this->delay . "秒\n");
        
        // 包装调用链数据
        $taskData = [
            'className' => $this->className,
            'callChain' => $this->callChain
        ];
        
        // 投递到异步队列
        $result = AsyncQueue::pushTask('DelayDispatcher', $taskData, $this->delay);
        
        print_r("任务投递结果: " . ($result ? "成功" : "失败") . "\n");
        
        return $result;
    }
    
    /**
     * 处理异步代理任务
     * @param array $taskData 任务数据
     * @return mixed
     */
    public static function handleProxyTask(array $taskData)
    {
        // 添加明显的调试信息，确保能看到该方法被调用
        print_r("\n=== AsyncProxy::handleProxyTask 被调用 ===\n");
        print_r("类名: " . $taskData['className'] . "\n");
        print_r("调用链: " . json_encode($taskData['callChain']) . "\n");
        
        try {
            $className = $taskData['className'];
            $callChain = $taskData['callChain'];
            
            if (!class_exists($className)) {
                error_log('AsyncProxy: 类不存在: ' . $className);
                return false;
            }
            
            // 创建类实例
            $instance = new $className();
            
            // 执行调用链
            $result = $instance;
            foreach ($callChain as $call) {
                list($method, $args) = $call;
                if (!method_exists($result, $method)) {
                    error_log('AsyncProxy: 方法不存在: ' . get_class($result) . '::' . $method);
                    return false;
                }
                $result = call_user_func_array([$result, $method], $args);
                
                // 如果中间结果为null或false，终止调用链
                if ($result === null || $result === false) {
                    break;
                }
            }
            
            return $result;
        } catch (\Throwable $e) {
            error_log('AsyncProxy: 执行任务时出错: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            return false;
        }
    }
}
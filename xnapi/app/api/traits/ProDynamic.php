<?php

namespace app\api\traits;

/**
 * Coroutine 类库
 * 实现动态方法调用和链式调用功能
 */
class ProDynamic
{
    private $methodValue; // 方法的值
    private $classValue;  // 类的值
    private $calledMethod; // 被调用方法
    private $calledParams; // 被调用方法的参数
    private $delayTime = 0;    // 延迟时间
    private $callChain = []; // 调用链记录
    private $syncCalled = false; // sync方法是否已被调用

    /**
     * 处理静态动态方法调用
     * 如：Proed::module('Miaoappecard')
     *
     * @param string $name 方法名
     * @param array $arguments 参数
     * @return Proed
     */
    public static function __callStatic($name, $arguments)
    {
        $instance = new self();
        $instance->methodValue = $name;
        // 第一个参数作为类的值
        $instance->classValue = !empty($arguments) ? $arguments[0] : '';
        
        // 记录调用链
        $instance->callChain[] = [
            'type' => 'static',
            'name' => $name,
            'args' => $arguments
        ];
        
        return $instance;
    }



    /**
     * 同步方法，打印调用信息
     *
     * @return void
     */
    public function sync()
    {
        // 如果 sync 方法已经被调用，则不再执行
        if ($this->syncCalled) {
            return;
        }
        
        // 打印所需信息
        echo "方法的值：{$this->methodValue}，";
        echo "类的值：{$this->classValue}，";
        echo "被调用方法：{$this->calledMethod}，";
        echo "被调用方法的传递参数：";
        echo "延迟时间：{$this->delayTime}，";
        
        echo PHP_EOL;
        
        // 标记 sync 方法已被调用
        $this->syncCalled = true;
        
        $task = [
            //'callChain'    => $this->callChain,
            'methodValue'  => $this->methodValue,
            'className'    => $this->classValue,
            'calledMethod' => $this->calledMethod,
            'calledParams' => $this->calledParams,
            'delay' => $this->delayTime
        ];
        
        \os\AsyncQueue::pushTask('DelayDispatcher', $task, $this->delayTime);
        
        return;
        // 如果有延迟，打印延迟信息
        if (!empty($this->delayTime)) {
            echo "延迟时间：{$this->delayTime}秒" . PHP_EOL;
        }
        
        // 也可以选择将任务推送到异步队列（类似 ProTrait 中的做法）
        if (!empty($this->callChain)) {
            $task = [
                'data' => [
                    'callChain' => $this->callChain,
                    'className' => $this->classValue,
                    'methodValue' => $this->methodValue,
                    'calledMethod' => $this->calledMethod,
                    'calledParams' => $this->calledParams,
                    'delay' => $this->delayTime
                ]
            ];
            
            // 如果存在 DelayDispatcher 类，则将任务推送到队列
            if (class_exists('\os\AsyncQueue')) {
                \os\AsyncQueue::pushTask('DelayDispatcher', $task, $this->delayTime);
            }
        }
    }

    /**
     * 处理链式调用中的第一个方法（如 module('Miaoappecard')）
     * 这是为了支持 Pro::worked('2')->module('Miaoappecard')->test('222') 这种调用方式
     *
     * @param string $name 方法名
     * @param array $arguments 参数
     * @return $this
     */
    public function __call($name, $arguments)
    {
        // 特殊处理 delay 方法
        if ($name === 'delay') {
            $this->delayTime = !empty($arguments) ? $arguments[0] : 0;
            // 记录延迟调用
            $this->callChain[] = [
                'type' => 'delay',
                'time' => $this->delayTime
            ];
        } else {
            // 如果 methodValue 还没有设置，说明这是链式调用中的第一个方法（如 module('Miaoappecard')）
            if (empty($this->methodValue)) {
                $this->methodValue = $name;
                $this->classValue = !empty($arguments) ? $arguments[0] : '';
                
                // 记录静态调用链
                $this->callChain[] = [
                    'type' => 'static',
                    'name' => $name,
                    'args' => $arguments
                ];
            } else {
                // 否则这是后续的方法调用（如 ->test('222')）
                $this->calledMethod = $name;
                $this->calledParams = $arguments;
                
                // 记录方法调用链
                $this->callChain[] = [
                    'type' => 'method',
                    'name' => $name,
                    'args' => $arguments
                ];
            }
        }
        
        // 支持链式调用
        return $this;
    }
    
    /**
     * 获取调用链信息
     *
     * @return array
     */
    public function getCallChain()
    {
        return $this->callChain;
    }
    
    /**
     * 析构函数
     * 在对象销毁时自动调用 sync 方法（如果还没有调用过）
     */
    public function __destruct()
    {
        // 如果 sync 方法还没有被调用，则自动调用
        if (!$this->syncCalled) {
            $this->sync();
        }
    }
}
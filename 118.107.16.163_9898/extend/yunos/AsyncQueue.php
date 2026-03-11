<?php
namespace yunos;

class AsyncQueue
{
    // Redis连接配置
    protected static $redisHost  = null;
    protected static $redisPort  = null;
    protected static $redisAuth  = null;
    protected static $redisDb    = null;
    protected static $stream     = 'os:async:stream';
    protected static $delay_zset = 'os:async:stream:delay';
    protected static $group      = 'queue_group';
    protected static $consumer   = 'consumer_1';
    
    /**
     * 获取Redis配置
     */
    protected static function getRedisConfig()
    {
        return [
            'host' => getenv('queue_host') ?: '127.0.0.1',
            'port' => getenv('queue_port') ?: 6379,
            'auth' => getenv('queue_auth') ?: null,
            'db'   => getenv('queue_db') ?: 0,
        ];
    }
    protected static function server_id()
    {
        return getenv('server_id') ?: '';
    }
    protected static function getStream()
    {
        return getenv('queue_stream') ?: 'os:async:stream';
    }

    protected static function getDelayZset()
    {
        return getenv('queue_delay_zset') ?: 'os:async:stream:delay';
    }

    /**
     * 推送任务到指定 stream
     * @param string $stream stream 名称（仅为后缀）
     * @param mixed $data 任务数据
     * @param int $delay 延迟秒数
     */
    public static function pushToStream($stream, $data, $delay = 0)
    {
        $redis = self::redis();
        $streamKey = self::getStream() . '_' . $stream;
        $delayKey = $streamKey . ':delay';
        $payload = json_encode([
            'data' => $data,
            'time' => time(),
        ]);
        if ($delay > 0) {
            $score = time() + $delay;
            return $redis->zAdd($delayKey, $score, $payload);
        } else {
            return $redis->xAdd($streamKey, '*', ['payload' => $payload]);
        }
    }

    /**
     * 推送任务到指定业务（自动生成stream名）
     * @param string $task 任务/业务名
     * @param mixed $data 任务数据
     * @param int $delay 延迟秒数
     */
    public static function pushTask($task, $data, $delay = 0)
    {
        return self::pushToStream($task, $data, $delay);
    }

    // 消费任务（建议在Webman自定义进程中调用）
    public static function consume(callable $callback)
    {
        $redis = self::redis();
        // 创建消费组（幂等）
        try {
            $redis->xGroup('CREATE', self::$stream, self::$group, '0', true);
        } catch (\RedisException $e) {}

        while (true) {
            // 读取任务
            $messages = $redis->xReadGroup(self::$group, self::$consumer, [self::$stream => '>'], 1, 5);
            if ($messages && isset($messages[self::$stream])) {
                foreach ($messages[self::$stream] as $id => $fields) {
                    $payload = json_decode($fields['payload'], true);
                    try {
                        $callback($payload['data']);
                        $redis->xAck(self::$stream, self::$group, [$id]);
                    } catch (\Throwable $e) {
                        // 失败可记录日志或重试
                    }
                }
            } else {
                usleep(200000); // 无任务时短暂休眠
            }
        }
    }

    /**
     * 竞态消费：同 group 内只有一个 consumer 消费消息
     * @param string $stream stream 名称
     * @param string $group 消费组名
     * @param string $consumer 消费者名
     * @param callable $callback 处理函数
     */
    public static function competeConsume($stream, $group, $consumer, callable $callback)
    {
        $redis = self::redis();
        // 创建 group（幂等）
        try {
            $redis->xGroup('CREATE', $stream, $group, '0', true);
        } catch (\RedisException $e) {}

        while (true) {
            $messages = $redis->xReadGroup($group, $consumer, [$stream => '>'], 1, 5);
            if ($messages && isset($messages[$stream])) {
                foreach ($messages[$stream] as $id => $fields) {
                    $payload = json_decode($fields['payload'], true);
                    try {
                        $callback($payload);
                        $redis->xAck($stream, $group, [$id]);
                    } catch (\Throwable $e) {
                        // 失败可记录日志或重试
                    }
                }
            } else {
                usleep(200000);
            }
        }
    }

    /**
     * 广播消费：每个 group 都能消费所有消息
     * @param string $group 消费组名
     * @param string $consumer 消费者名
     * @param callable $callback 处理函数
     * @param string|null $stream 指定stream名，默认null用主stream
     */
    public static function broadcastConsume($group, $consumer, callable $callback, $stream = null)
    {
        $redis = self::redis();
        $stream = $stream ?: self::$stream;
        // 创建 group（幂等）
        try {
            $redis->xGroup('CREATE', $stream, $group, '0', true);
        } catch (\RedisException $e) {}

        while (true) {
            $messages = $redis->xReadGroup($group, $consumer, [$stream => '>'], 1, 5);
            if ($messages && isset($messages[$stream])) {
                foreach ($messages[$stream] as $id => $fields) {
                    $payload = json_decode($fields['payload'], true);
                    try {
                        $callback($payload);
                        $redis->xAck($stream, $group, [$id]);
                    } catch (\Throwable $e) {
                        // 失败可记录日志或重试
                    }
                }
            } else {
                usleep(200000);
            }
        }
    }

    /**
     * 自动消费：根据模式和任务名自动消费
     * @param string $mode 消费模式，'broadcast' 或 'compete'
     * @param string $task 任务/业务名
     * @param callable $callback 处理函数
     */
    public static function autoConsume($mode, $task, callable $callback)
    {
        $stream = self::getStream() . '_' . $task;
        // group 自动加主机名+进程号，保证唯一
        //getenv('svever_id')
        $server = 's';
        if ($mode === 'broadcast'){
            $server = self::server_id();
        }
        
        $group = $task . '_group_' . $server;
        $consumer = $task . '_consumer_' . getmypid();
    
        if ($mode === 'broadcast') {
            self::broadcastConsume($group, $consumer, $callback, $stream);
        } else {
            self::competeConsume($stream, $group, $consumer, $callback);
        }
    }
    
    public static function handleDelayByTask($task)
    {
        try {
            $redis = self::redis();
            $streamKey = self::getStream() . '_' . $task;
            $delayKey = $streamKey . ':delay';
            $now = time();
            $tasks = $redis->zRangeByScore($delayKey, 0, $now);
            foreach ($tasks as $payload) {
                $redis->xAdd($streamKey, '*', ['payload' => $payload]);
                $redis->zRem($delayKey, $payload);
            }
        } catch (\RedisException $e) {
            error_log('Redis操作失败: ' . $e->getMessage());
        }
    }
    
    public static function trimExpiredMessages(array $tasks, int $expire)
    {
        $redis = self::redis();
        $now = time();
        foreach ($tasks as $task) {
            $stream = self::getStream() . '_' . $task;
            $messages = $redis->xRange($stream, '-', '+');
            foreach ($messages as $id => $fields) {
                $payload = json_decode($fields['payload'] ?? '', true);
                if (isset($payload['time']) && $now - $payload['time'] > $expire) {
                    $redis->xDel($stream, [$id]);
                }
            }
        }
    }

    // 获取redis实例
    /*protected static function redis()
    {
        // 这里建议用 webman/redis 插件或自定义连接
        // return \support\Redis::connection('default');
        static $redis = null;
        if (!$redis) {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
        }
        return $redis;
    }*/
    public static function redis()
    {
        static $redis = null;
        if (!$redis) {
            $config = self::getRedisConfig();
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port']);
            if ($config['auth']) {
                $redis->auth($config['auth']);
            }
            if ($config['db']) {
                $redis->select($config['db']);
            }
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, 3);
            $redis->setOption(\Redis::OPT_TCP_KEEPALIVE, 1);
        }
        return $redis;
    }
} 
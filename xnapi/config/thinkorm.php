<?php

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            // 数据库类型
            'type' => 'mysql',
            'hostname' => getenv('DB_HOST') ?: '127.0.0.1',
            'database' => getenv('DB_DATABASE') ?: 'xndata',
            'username' => getenv('DB_USERNAME') ?: 'xndata',
            'password' => getenv('DB_PASSWORD') ?: '8Zr6zNPfhczwm2s4',
            'hostport' => getenv('DB_PORT') ?: '3306',
            // 数据库连接参数
            'params' => [
                // 连接超时3秒
                \PDO::ATTR_TIMEOUT => 3,
            ],
            // 数据库编码默认采用utf8
            'charset' => 'utf8',
            // 数据库表前缀
            'prefix' => 'fa_',
            // 断线重连
            'break_reconnect' => true,
            // 关闭SQL监听日志
            'trigger_sql' => false,
            // 自定义分页类
            'bootstrap' =>  ''
        ],
    ],
];

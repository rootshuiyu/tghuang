<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

global $argv;

return [
    // File update detection and automatic reload
    'monitor' => [
        'handler' => app\process\Monitor::class,
        'reloadable' => false,
        'constructor' => [
            // Monitor these directories
            'monitorDir' => array_merge([
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
            ], glob(base_path() . '/plugin/*/app'), glob(base_path() . '/plugin/*/config'), glob(base_path() . '/plugin/*/api')),
            // Files with these suffixes will be monitored
            'monitorExtensions' => [
                'php', 'html', 'htm', 'env'
            ],
            'options' => [
                'enable_file_monitor' => !in_array('-d', $argv) && DIRECTORY_SEPARATOR === '/',
                'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
            ]
        ]
    ],
    'OrderExpired'  => [
        'handler'  => app\process\OrderExpired::class
    ],
    'OrderQuery'  => [
        'handler'  => app\process\OrderQuery::class
    ],
    'AccountActive'  => [
        'handler'  => app\process\AccountActive::class
    ],
    'NotifyOmission'  => [
        'handler'  => app\process\NotifyOmission::class
    ],
    'yoyo-async-queue-delay-processor' => [
        'handler' => app\queue\AsyncQueueDelayProcessor::class,
        'count'   => 20,
        'reloadable' => true,
    ],
    'yoyo-async-queue-OrderBuild' => [
        'handler' => app\queue\OrderBuild::class,
        'count'   => 10,
        'reloadable' => true,
    ],
    'yoyo-async-queue-OrderNotify' => [
        'handler' => app\queue\OrderNotify::class,
        'count'   => 20,
        'reloadable' => true,
    ],
    'yoyo-async-queue-AsyncOrderQuery' => [
        'handler' => app\queue\AsyncOrderQuery::class,
        'count'   => 50,
        'reloadable' => true,
    ],
    'yoyo-async-queue-DelayDispatcher' => [
        'handler' => app\queue\DelayDispatcher::class,
        'count'   => 20,
        'reloadable' => true,
    ],
];

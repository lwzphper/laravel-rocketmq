<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/29 11:34,
 * @LastEditTime: 2021/10/29 11:34
 */
return [
    'queuelog' => [
        'driver' => 'daily',
        'path' => storage_path('logs/queue.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 50,
    ],
];
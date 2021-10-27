<?php
return [
    /**
     * 默认组配置
     */
    'default' => 'push',
    /*
    |--------------------------------------------------------------------------
    | Default RocketMQ Connection Config
    |--------------------------------------------------------------------------
    |
     */
    'http_endpoint' => env('ROCKETMQ_HTTP_ENDPOINT'),
    'access_key' => env('ROCKETMQ_ACCESS_KEY'),
    'secret_key' => env('ROCKETMQ_SECRET_KEY'),
    'group' => [ // 分组
//        '分组名称' => [
//            'topic' => 'topic',
//            'instance_id' => '实例id',
//            'group_id' => '分组id',
//        ],
    ],

    // 路由配置。 msg_tag => 处理类
    'routes' => [
    ],
];

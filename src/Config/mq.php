<?php
/**
 * 消息队列相关配置
 */
return [
    'mq_type' => 'RocketMQ', // 队列类型（目前只支持 RocketMQ）。 RocketMQ、RabbitMQ、Kafka
    'reproduce_max_num' => 5, // 最大重新投递次数
    'reproduce_time' => 600, // 重新投递的时间（相当于更新时间）

    /**
     * rocketmq 相关配置
     */
    'rocketmq' => [
        // 默认分组
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
    ]
];

<?php
/**
 * 消息队列相关配置
 */
return [
    'mq_type' => 'RocketMQ', // 队列类型（目前只支持 RocketMQ）。 RocketMQ、RabbitMQ、Kafka、Redis
    'reproduce_max_num' => 5, // 最大重新投递次数
    'reproduce_time' => 600, // 重新投递的时间（相当于更新时间）

    /**
     * rocketmq 相关配置。队列关键参数：
     * instance_id => topic => message tag
     *
     * Group ID
     *
     * message tag：消息标签
     *
     * msg key：消息标识（自动生成）
     */
    'rocketmq' => [
        // 默认分组
        'default' => 'default',
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
            'clue' => [
                'instance_id' => '实例id',
                'topic' => 'topic',
                'msg_tag' => '消息标签',
            ],
        ],

        // 路由配置。 msg_tag => 处理类
        'routes' => [
        ],
    ]
];

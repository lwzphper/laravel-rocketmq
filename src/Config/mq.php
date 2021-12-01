<?php
/**
 * 消息队列相关配置
 */
return [
    'mq_type' => 'RocketMQ', // 队列类型（目前只支持 RocketMQ）。 RocketMQ、RabbitMQ、Kafka、Redis
    'reproduce_max_num' => 5, // 最大重新投递次数
    'reproduce_time' => 600, // 重新投递的时间（相当于更新时间）
    'log_driver' => 'queuelog', // 日志驱动

    /**
     * rocketmq 相关配置。队列关键参数：
     * instance_id => topic => message tag
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
        'topic_group' => [ // topic分组
            'scrm' => [ // scrm实例
                'instance_id' => '实例id',
                'topic' => 'topic',
            ]
        ],
        'consume_group' => [ // 消费者分组
            'add_clue' => [ // 消费组名称
                'msg_tag' => '', // 消息标签
                'group_id' => 'scrm_clue', // 分组id
                'handle_class' => '', // 处理的消息的类名。必须继承 Lwz\LaravelExtend\MQ\Interfaces\ConsumerInterface 接口
            ],
        ],
    ]
];

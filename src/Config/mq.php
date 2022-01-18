<?php
/**
 * 消息队列相关配置
 */

use Lwz\LaravelExtend\MQ\Constants\MQConst;

return [
    'mq_type' => MQConst::TYPE_ROCKETMQ, // 队列类型（目前只支持 RocketMQ、Redis）。 RocketMQ、RabbitMQ、Kafka、Redis
    'reproduce_max_num' => 5, // 最大重新投递次数
    'reproduce_time' => 600, // 重新投递的时间（投递多久后没有被消费，重新投递）
    'log_driver' => 'queuelog', // 日志驱动

    'delete_send_log_stage' => MQConst::DEL_SEND_LOG_MSG_ID,  // 删除发送日志的阶段

    'save_produce_log' => true, // 是否记录发送日志
    'save_consumer_log' => true, // 是否记录消费日志

    /**
     * rocketmq 相关配置。队列关键参数：
     * instance_id => topic => message tag
     */
    'rocketmq' => [
        /*
        |--------------------------------------------------------------------------
        | Default RocketMQ Connection Config
        |--------------------------------------------------------------------------
        |
         */
        'http_endpoint' => env('ROCKETMQ_HTTP_ENDPOINT'),
        'access_key' => env('ROCKETMQ_ACCESS_KEY'),
        'secret_key' => env('ROCKETMQ_SECRET_KEY'),
        // 消息标签后缀（empty函数值为true则视为不设置），主要为了解决一个队列，开发、测试、正式环境同时使用（虽然这种做法不推荐）
        'msg_tag_ext' => env('APP_ENV'),
        'topic_group' => [ // topic分组
//            'scrm' => [ // scrm实例
//                'instance_id' => '实例id',
//                'topic' => 'topic名称',
//            ]
        ],
        'consume_group' => [ // 消费者分组
//            'add_clue' => [ // 消费组名称
//                'msg_tag' => 'clue', // 消息标签
//                'group_id' => 'scrm_clue', // 分组id
//                'handle_class' => '', // 处理的消息的类名。必须继承 Lwz\LaravelExtend\MQ\Interfaces\ConsumerInterface 接口
//            ],
        ],
    ]
];

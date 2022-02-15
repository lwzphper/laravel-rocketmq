<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/12/01 15:30,
 * @LastEditTime: 2021/12/01 15:30
 */

namespace Lwz\LaravelExtend\MQ\Constants;

class MQConst
{
    /**
     * MQ 类型
     */
    public const TYPE_ROCKETMQ = 'RocketMQ';
//    public const TYPE_REDIS = 'Redis';

    /**
     * 删除发送日志的阶段
     */
    // 获取到 消息id 删除日志。
    // 优势：可以跨数据库实例进行通讯，确保消息已经成功发送到 broker
    // 弊端：如果使用异步刷盘机制，可能由于 broker 没有及时刷盘导致消息丢失
    public const DEL_SEND_LOG_MSG_ID = 1;
    // 被消费者消费。
    // 优势：可以确保消息成功被消费者消费。
    // 弊端：
    //  + 只能相同数据库实例下事务，不支持跨数据库实例的事务；
    //  + 如果多个消费组同时消费，只会有一个消费组删除消息成功，其他消费组再去删除，会浪费请求
    public const DEL_SEND_LOG_CONSUMER = 2;

    /**
     * 字段定义
     */
    public const KEY_DELETE_SEND_LOG_STAGE = 'dsl_stage'; // 删除发送日志的阶段

    /**
     * 用户数据的key
     */
    public const KEY_USER_DATA = 'data';
}
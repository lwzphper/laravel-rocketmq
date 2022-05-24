<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/28 10:21,
 * @LastEditTime: 2021/10/28 10:21
 */

namespace Lwz\LaravelExtend\MQ\Library;

use Lwz\LaravelExtend\MQ\Constants\MQConst;
use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;
use Lwz\LaravelExtend\MQ\Library\RocketMQ\Constant;
use Lwz\LaravelExtend\MQ\Library\RocketMQ\RocketReliableConsumer;
use Lwz\LaravelExtend\MQ\Library\RocketMQ\RocketReliableMultiProducer;
use Lwz\LaravelExtend\MQ\Library\RocketMQ\RocketReliableProducer;

/**
 * Class MQProducerFactory
 * @package Lwz\LaravelExtend\MQ\Library
 * @author lwz
 * 队列生产者工厂
 */
class MQProducer
{
    /**
     * 获取生产者
     * @param array $params 队列参数
     *   RocketMQ参数：
     *      topic_group: topic分组名（单条数据发送时，必填）
     *      msg_tag: 消息标签
     *      msg_key: 消息唯一标识（可以做幂等性处理）
     *      delay_time: 延迟时间戳（具体时间的时间戳，如：strotime(2022-10-10 10:32:43)）
     *      multi_data: true|false 。是否发送多条消息（只支持相同 topic）
     * @return MQReliableProducerInterface
     * @author lwz
     */
    public static function getProducer(array $params): MQReliableProducerInterface
    {

        switch (self::getMQType($params)) {
            case MQConst::TYPE_ROCKETMQ:
                return self::_createRocketMQProducer($params);
            default:
                throw new MQException('目前支持该队列');
        }
    }

    /**
     * 获取消费者
     * @param array $params 队列参数
     *   RocketMQ参数：
     *      msg_group: 消息分组，对应配置文件中的 rocketmq.msg_group 下的分组名
     *      msg_tag: 消息标签
     *      msg_num: 每次消费的消息数量(最多可设置为16条)
     *      wait_seconds: 长轮询时间（最多可设置为30秒）
     * @return MQReliableConsumerInterface
     * @throws MQException
     * @author lwz
     */
    public static function getConsumer(array $params): MQReliableConsumerInterface
    {
        switch (self::getMQType($params)) {
            case 'RocketMQ':
                return self::_createRocketMQConsumer($params);
            default:
                throw new MQException('[mq error] 目前支持该队列');
        }
    }

    /**
     * 获取队列类型
     * @param array $params 请求参数
     * @return string
     * @author lwz
     */
    protected static function getMQType(array $params): ?string
    {
        $mqType = $params['mq_type'] ?? null;
        return $mqType ?? config('mq.mq_type');
    }

    /**
     * rocketMQ 生产者
     * @param array $params 请求参数
     *      topic_group: topic分组名（单条数据发送时，必填）
     *      msg_tag: 消息标签
     *      msg_key: 消息唯一标识（可以做幂等性处理）
     *      delay_time: 延迟时间戳（具体时间的时间戳，如：strotime(2022-10-10 10:32:43)）
     *      multi_data: true|false 。是否发送多条消息（只支持相同 topic）
     *      add_msg_tag_ext: true|false 。是否添加消息标签后缀。会覆盖配置文件的选项
     * @return MQReliableProducerInterface
     * @throws MQException
     * @author lwz
     */
    protected static function _createRocketMQProducer(array $params): MQReliableProducerInterface
    {
        self::_checkRocketMQParamsOrFail($params);

        // 批量添加
        if ($params[Constant::FIELD_MULTI_DATA] ?? null) {
            return new RocketReliableMultiProducer();
        }

        // 添加单条
        return new RocketReliableProducer(
            $params[Constant::FIELD_TOPIC_GROUP],
            $params[Constant::FIELD_TAG] ?? null,
            $params[Constant::FIELD_MSG_KEY] ?? null,
            $params[Constant::FIELD_DELAY_TIME] ?? null,
            $params[Constant::FIELD_ADD_MSG_TAG_EXT] ?? true,
        );
    }

    /**
     * rocketMQ 消费者
     * @param array $params
     *   config_group: 配置文件中的 分组名
     *   msg_num: 每次消费的消息数量(最多可设置为16条)
     *   wait_seconds: 长轮询时间（最多可设置为30秒）
     * @return MQReliableConsumerInterface
     * @author lwz
     */
    protected static function _createRocketMQConsumer(array $params): MQReliableConsumerInterface
    {
        self::_checkRocketMQParamsOrFail($params, true);

        return new RocketReliableConsumer(
            $params['topic_group'],
            $params['consume_group'],
            $params['msg_num'] ?? 3,
            $params['wait_seconds'] ?? 3
        );
    }

    /**
     * 获取 RocketMQ 的消息信息
     * @param array $params 参数
     *      topic_group: topic分组名（单条数据发送时，必填）
     *      msg_tag: 消息标签
     *      msg_key: 消息唯一标识（可以做幂等性处理）
     *      delay_time: 延迟时间戳（具体时间的时间戳，如：strotime(2022-10-10 10:32:43)）
     *      multi_data: true|false 。是否发送多条消息（只支持相同 topic）
     *      add_msg_tag_ext: true|false 。是否添加消息标签后缀。会覆盖配置文件的选项
     * @param bool $isConsume 是否消费消息
     * @author lwz
     */
    protected static function _checkRocketMQParamsOrFail(array $params, bool $isConsume = false)
    {
        $isMultiData = $params[Constant::FIELD_MULTI_DATA] ?? null;
        // 必要的参数验证（单条数据发送，topic_group 必填）
        if (!$isMultiData && empty($params['topic_group'] ?? null)) {
            throw new MQException('[mq error] 缺少参数：topic_group');
        }

        // 消费消息，消费组必填
        if ($isConsume && empty($params['consume_group'] ?? null)) {
            throw new MQException('[mq error] 缺少参数：consume_group');
        }
    }

}

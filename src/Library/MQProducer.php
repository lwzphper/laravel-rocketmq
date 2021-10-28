<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/28 10:21,
 * @LastEditTime: 2021/10/28 10:21
 */

namespace Lwz\LaravelExtend\MQ\Library;

use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;
use Lwz\LaravelExtend\MQ\Library\RocketMQ\RocketReliableConsumer;
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
     * @return MQReliableProducerInterface
     * @throws MQException
     * @author lwz
     */
    public static function getProducer(array $params): MQReliableProducerInterface
    {
        switch (config('mq.mq_type')) {
            case 'RocketMQ':
                return self::_createRocketMQProducer($params);
            default:
                throw new MQException('目前支持该队列');
        }
    }

    /**
     * 获取消费者
     * @param array $params 队列参数
     * @return MQReliableConsumerInterface
     * @throws MQException
     * @author lwz
     */
    public static function getConsumer(array $params): MQReliableConsumerInterface
    {
        switch (config('mq.mq_type')) {
            case 'RocketMQ':
                return self::_createRocketMQConsumer($params);
            default:
                throw new MQException('目前支持该队列');
        }
    }

    /**
     * rocketMQ 生产者
     * @param array $params 请求参数
     *      msg_tag: 消息标签
     *      msg_key: 消息唯一标识（不传，会自动生成一个唯一标识）
     *      config_group: 配置文件中的 分组名
     *      delay_time: 延迟时间戳（具体时间的时间戳，如：strotime(2022-10-10 10:32:43)）
     * @return MQReliableProducerInterface
     * @author lwz
     */
    protected static function _createRocketMQProducer(array $params): MQReliableProducerInterface
    {
        return new RocketReliableProducer(
            $params['msg_tag'],
            $params['config_group'],
            $params['msg_key'] ?? null,
            $params['delay_time'] ?? null
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
        return new RocketReliableConsumer(
            $params['config_group'],
            $params['msg_num'] ?? 3,
            $params['wait_seconds'] ?? 3,
        );
    }

}
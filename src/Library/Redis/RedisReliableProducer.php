<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/12/1 22:56,
 * @LastEditTime: 2021/12/1 22:56
 */
declare(strict_types=1);

namespace Lwz\LaravelExtend\MQ\Library\Redis;


use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;
use Lwz\LaravelExtend\MQ\Traits\ProducerTrait;

class RedisReliableProducer implements MQReliableProducerInterface
{
    use ProducerTrait;

    public function __construct()
    {
        // 初始操作
        $this->init();
    }

    /**
     * 简单的推送队列（不会记录消息状态，主要用户消息重新投递）
     * @param array $payload
     * @return mixed
     * @author lwz
     */
    public function simplePublish(array $payload)
    {
        // TODO: Implement simplePublish() method.
    }

    /**
     * 发布消息
     * @return mixed
     * @author lwz
     */
    public function publishMessage()
    {
        // TODO: Implement publishMessage() method.
    }
}
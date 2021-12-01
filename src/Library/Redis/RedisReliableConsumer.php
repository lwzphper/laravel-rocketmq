<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/12/1 22:55,
 * @LastEditTime: 2021/12/1 22:55
 */
declare(strict_types=1);

use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;

class RedisReliableConsumer implements MQReliableConsumerInterface
{
    public function __construct(array $params)
    {

    }

    /**
     * 消费
     * @return mixed
     * @author lwz
     */
    public function consumer()
    {
        
    }
}
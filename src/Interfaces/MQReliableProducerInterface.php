<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 17:55,
 * @LastEditTime: 2021/10/27 17:55
 */

namespace Lwz\LaravelExtend\MQ\Interfaces;

/**
 * Interface MQReliableInterface
 * @package App\Common\Library\MQ
 * @auth lwz
 * MQ 可靠投递接口
 */
interface MQReliableProducerInterface
{
    /**
     * 简单的推送队列（不会记录消息状态，主要用户消息重新投递）
     * @param array $payload
     * @return mixed
     * @author lwz
     */
    public function simplePublish(array $payload);

    /**
     * 发布消息准备（记录消息状态）
     * @param array $payload 消息内容
     * @author lwz
     */
    public function publishPrepare(array $payload);

    /**
     * 发布消息
     * @return mixed
     * @author lwz
     */
    public function publishMessage();
}
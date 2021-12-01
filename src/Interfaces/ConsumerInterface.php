<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/12/01 16:50,
 * @LastEditTime: 2021/12/01 16:50
 */

namespace Lwz\LaravelExtend\MQ\Interfaces;

/**
 * Interface ConsumerInterface
 * @package Lwz\LaravelExtend\MQ\Interfaces
 * 消费者接口
 */
interface ConsumerInterface
{
    /**
     * 消费消息
     * @param string $msgBody 消息体
     * @param string $msgKey 消息唯一标识
     * @return mixed
     */
    public function handle(string $msgBody, string $msgKey);
}
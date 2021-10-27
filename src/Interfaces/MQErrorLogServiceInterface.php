<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:22,
 * @LastEditTime: 2021/10/27 18:22
 */

namespace Lwz\LaravelExtend\MQ\Interfaces;


use Lwz\LaravelExtend\MQ\Models\MQErrorLog;

interface MQErrorLogServiceInterface
{
    /**
     * 添加
     * @param string $mqUuid mq唯一标识
     * @param array|string $payload 负载，消息体
     * @param array $mqConfig mq配置
     * @param string $errMsg 错误信息
     * @return mixed
     */
    public function addData(string $mqUuid, $payload, array $mqConfig, string $errMsg): MQErrorLog;
}
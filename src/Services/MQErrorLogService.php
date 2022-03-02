<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:34,
 * @LastEditTime: 2021/10/27 18:34
 */

namespace Lwz\LaravelExtend\MQ\Services;


use Lwz\LaravelExtend\MQ\Interfaces\MQErrorLogServiceInterface;
use Lwz\LaravelExtend\MQ\Models\MQErrorLog;
use Lwz\LaravelExtend\MQ\Repositories\MQErrorLogRepository;
use Lwz\LaravelExtend\MQ\Library\MQHelper;

class MQErrorLogService implements MQErrorLogServiceInterface
{
    /**
     * 添加
     * @param string $mqUuid mq唯一标识
     * @param array|string $payload 负载，消息体
     * @param array $mqConfig mq配置
     * @param string $errMsg 错误信息
     * @return mixed
     */
    public function addData(string $mqUuid, $payload, array $mqConfig, string $errMsg): MQErrorLog
    {
        return MQErrorLogRepository::add([
            'mq_uuid' => $mqUuid,
            'mq_config' => MQHelper::encodeData($mqConfig),
            'payload' => is_string($payload) ? $payload : MQHelper::encodeData($payload),
            'error_msg' => $errMsg,
        ]);
    }
}
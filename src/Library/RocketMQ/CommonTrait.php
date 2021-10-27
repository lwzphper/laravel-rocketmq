<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:05,
 * @LastEditTime: 2021/10/27 18:05
 */

namespace Lwz\LaravelExtend\MQ\Library\RocketMQ;


use Illuminate\Support\Facades\Log;
use Lwz\LaravelExtend\MQ\Interfaces\MQErrorLogServiceInterface;

trait CommonTrait
{
    /**
     * 处理错误
     * @param \Throwable $t
     * @param string $msgKey 消息key
     * @param array|string $payload 消息体
     * @param array $mqConfig mq配置信息
     * @author lwz
     */
    private function _handleError(\Throwable $t, string $msgKey, $payload, array $mqConfig)
    {
        $errMsg = '队列错误消息：' . $t->getMessage() . ' trace: ' . $t->getTraceAsString();
        // 记录错误信息
        app(MQErrorLogServiceInterface::class)->addData($msgKey, $payload, $mqConfig, $errMsg);

        // 记录日志文件
        Log::error($errMsg);
    }
}
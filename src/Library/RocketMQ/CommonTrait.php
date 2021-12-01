<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:05,
 * @LastEditTime: 2021/10/27 18:05
 */

namespace Lwz\LaravelExtend\MQ\Library\RocketMQ;


use Illuminate\Support\Facades\Log;
use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQErrorLogServiceInterface;
use MQ\Model\TopicMessage;

trait CommonTrait
{
    /**
     * 实例id
     * @var string|null
     */
    protected ?string $instanceId;

    /**
     * topic
     * @var string
     */
    protected string $topic;

    /**
     * 设置MQ信息
     * @param string $topicGroup topic所属分组
     * @throws MQException
     * @author lwz
     */
    protected function _setMQInfo(string $topicGroup)
    {
        // 获取分组信息
        $topicInfo = config('mq.rocketmq.topic_group.' . $topicGroup);
        if (empty($topicInfo) || !is_array($topicInfo)) {
            throw new MQException('[mq error] 无法找到topic分组信息：' . $topicInfo);
        }

        // topic不能为空
        $topic = $topicInfo['topic'] ?? null;
        if (empty($topic)) {
            throw new MQException('[mq error] 请配置' . $topicGroup . '分组的topic信息：');
        }

        $this->instanceId = $topicInfo['instance_id'] ?? null;
        $this->topic = $topicInfo['topic'];
    }

    /**
     * 检查是否投递成功
     * @param TopicMessage $publishRet 投递的结果
     * @return bool
     * @author lwz
     */
    private function _checkIsProduceSuccess(TopicMessage $publishRet): bool
    {
        // 如果返回了 message id ，则视为投递成功
        return isset($publishRet->messageId) && !empty($publishRet->messageId);
    }

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
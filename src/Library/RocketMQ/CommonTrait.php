<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:05,
 * @LastEditTime: 2021/10/27 18:05
 */

namespace Lwz\LaravelExtend\MQ\Library\RocketMQ;


use Illuminate\Support\Facades\Log;
use Lwz\LaravelExtend\MQ\Constants\MQConst;
use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQErrorLogServiceInterface;
use Lwz\LaravelExtend\MQ\Library\MQHelper;
use MQ\Model\TopicMessage;
use Psr\Log\LoggerInterface;

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
     * 发送消息
     * @param string $instanceId 实例id
     * @param string $topic topic
     * @param array $payload 消息体
     * @param string|null $msgTag 消息标签
     * @param string|null $msgKey 消息key
     * @param int|null $delayTime 延迟时间
     * @return TopicMessage
     * @author lwz
     */
    protected function _sendMsg(
        string $instanceId, string $topic, array $payload,
        ?string $msgTag = null, ?string $msgKey = null, ?int $delayTime = null
    ): TopicMessage
    {
        // 获取生产者
        $producer = RocketMQClient::getInstance()->getClient()->getProducer($instanceId, $topic);

        // 发布消息
        $payload = MQHelper::encodeData($payload);
        $publishMessage = new TopicMessage($payload); //消息内容
        $publishMessage->setMessageTag($msgTag);//设置TAG
        // 设置消息KEY
        $publishMessage->setMessageKey($msgKey);
        // 延迟时间
        if ($delayTime) {
            $publishMessage->setStartDeliverTime($delayTime * 1000);
        }

        $publishRet = $producer->publishMessage($publishMessage);

        // 处理消息发送成功的相关操作
        if ($this->_checkIsProduceSuccess($publishRet)) {
            // 记录发送日志
            config('mq.save_produce_log') && $this->getLogDriver()->info(
                sprintf('消息生产成功。[msg_id] %s; [msg_tag] %s; [msg_key] %s; [msg_body] %s',
                    $publishRet->getMessageId(), $msgTag, $msgKey, $payload)
            );
        }

        return $publishRet;
    }

    /**
     * 设置MQ信息
     * @param string $topicGroup topic所属分组
     * @throws MQException
     * @author lwz
     */
    protected function _setMQInfo(string $topicGroup)
    {
//        // 获取分组信息
//        $topicInfo = config('mq.rocketmq.topic_group.' . $topicGroup);
//        if (empty($topicInfo) || !is_array($topicInfo)) {
//            throw new MQException('[mq error] 无法找到topic分组信息：' . $topicInfo);
//        }
//
//        // topic不能为空
//        $topic = $topicInfo['topic'] ?? null;
//        if (empty($topic)) {
//            throw new MQException('[mq error] 请配置' . $topicGroup . '分组的topic信息：');
//        }
//
//        $this->instanceId = $topicInfo['instance_id'] ?? null;
//        $this->topic = $topicInfo['topic'];

        list($this->instanceId, $this->topic) = $this->getTopicInfoOrFail($topicGroup);
    }

    /**
     * 获取 topic 信息
     * @param string $topicGroup topic所属分组
     * @return array
     * @author lwz
     */
    protected function getTopicInfoOrFail(string $topicGroup): array
    {
        // 获取分组信息
        $topicInfo = config('mq.rocketmq.topic_group.' . $topicGroup);
        if (empty($topicInfo) || !is_array($topicInfo)) {
            throw new MQException('[mq error] 无法找到topic分组信息：' . $topicInfo);
        }

        // instanceId 不能为空
        $instanceId = $topicInfo['instance_id'] ?? null;
        if (empty($instanceId)) {
            throw new MQException('[mq error] 请配置' . $topicGroup . '分组的instance_id信息：');
        }

        // topic不能为空
        $topic = $topicInfo['topic'] ?? null;
        if (empty($topic)) {
            throw new MQException('[mq error] 请配置' . $topicGroup . '分组的topic信息：');
        }

        return [$instanceId, $topic];
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
     * 设置分组id后缀
     * @param string $groupId 分组id
     * @return string
     * @author lwz
     */
    protected function setGroupIdExt(string $groupId): string
    {
        // 如果标签设置后缀，分组id也要设置响应后缀，否则同一个分组id消费不同消息标签，会有数据问题
        if ($ext = config('mq.rocketmq.msg_tag_ext')) {
            return $groupId . '_' . $ext;
        }
        return $groupId;
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

        // 记录日志文件（已经将错误记录到数据库，需要记录到磁盘日志？）
//        $this->getLogDriver()->error($errMsg);
    }


    /**
     * 获取日志驱动
     * @return LoggerInterface
     */
    protected function getLogDriver(): LoggerInterface
    {
        // 使用 Log::setDefaultDriver() 方法设置驱动，会同时修改业务代码的日志驱动，因此这里单独设置 channel
        return Log::channel(config('mq.log_driver'));
    }

    /**
     * 获取 MQ 日志的配置信息
     * @return array
     * @author lwz
     */
    protected function getMqLogConfig(
        ?string $topicGroup = null, ?string $msgTag = null,
        ?string $msgKey = null, ?string $delayTime = null
    ): array
    {
        return [
            'mq_type' => MQConst::TYPE_ROCKETMQ,
            'topic_group' => $topicGroup ?? ($this->topicGroup ?? null),
            'msg_tag' => $msgTag ?? ($this->msgTag ?? null),
            'msg_key' => $msgKey ?? ($this->msgKey ?? null),
            'delay_time' => $delayTime ?? ($this->delayTime ?? null),
        ];
    }

    /**
     * 加密数据
     * @param array $data 数据
     * @return string
     * @author lwz
     */
    protected function encodeData(array $data): string
    {
        return (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 解密数据
     * @param string $data
     * @return mixed
     * @author lwz
     */
    protected function decodeData(string $data): array
    {
        return json_decode($data, true);
    }
}

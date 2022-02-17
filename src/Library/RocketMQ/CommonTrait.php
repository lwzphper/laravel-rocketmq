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
     * 设置消息标签后缀
     * @param string $msgTag 消息标签
     * @return string
     */
    protected function setMsgTagExt(string $msgTag): string
    {
        if ($ext = config('mq.rocketmq.msg_tag_ext')) {
            $msgTagDelimiter = '|'; // 消息标签分隔符，考虑消费监听多个消息标签的情况
            return implode($msgTagDelimiter, array_map(function ($tag) use ($ext) {
                return $tag . '_' . $ext;
            }, explode($msgTagDelimiter, $msgTag)));
        }
        return $msgTag;
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

        // 记录日志文件
        $this->getLogDriver()->error($errMsg);
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
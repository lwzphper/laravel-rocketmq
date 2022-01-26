<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:52,
 * @LastEditTime: 2021/10/27 18:52
 */

namespace Lwz\LaravelExtend\MQ\Library\RocketMQ;

use Illuminate\Support\Facades\Log;
use Lwz\LaravelExtend\MQ\Constants\MQConst;
use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use MQ\Exception\MessageNotExistException;
use MQ\Model\Message;
use MQ\MQConsumer;

class RocketReliableConsumer implements MQReliableConsumerInterface
{
    use CommonTrait;

    /**
     * 分组id
     * @var string
     */
    protected string $groupId;

    /**
     * 消息标签
     * @var string|null
     */
    protected ?string $msgTag;

    /**
     * topic组
     * @var string
     */
    protected string $topicGroup;

    /**
     * 消费组
     * @var string
     */
    protected string $consumeGroup;

    /**
     * 每次消费的消息数量(最多可设置为16条)
     * @var int
     */
    protected int $msgNum;

    /**
     * 长轮询时间（最多可设置为30秒）
     * @var int
     */
    protected int $waitSeconds;

    /**
     * 消息处理对象
     * @var MQConsumerInterface
     */
    protected MQConsumerInterface $mqHandleObj;

    /**
     * 消费者对象
     * @var MQConsumer
     */
    protected MQConsumer $consumer;

    /**
     * RocketReliableConsumer constructor.
     * @param string $topicGroup topic组
     * @param int $msgNum 每次消费的消息数量(最多可设置为16条)
     * @param int $waitSeconds 长轮询时间（最多可设置为30秒）
     */
    public function __construct(string $topicGroup, string $consumeGroup, int $msgNum = 3, int $waitSeconds = 3)
    {
        // 更改日志驱动
        Log::setDefaultDriver(config('mq.log_driver'));
        // 设置基本信息
        $this->_setMQInfo($topicGroup);

        // 设置消费组信息
        $this->setConsumeGroupInfo($consumeGroup);

        $this->topicGroup = $topicGroup;
        $this->consumeGroup = $consumeGroup;
        $this->msgNum = $msgNum;
        $this->waitSeconds = $waitSeconds;

        $this->setConsumer();
    }

    /**
     * 设置消费组信息
     * @param string $consumeGroup 消费组
     */
    protected function setConsumeGroupInfo(string $consumeGroup)
    {
        $cgInfo = config('mq.rocketmq.consume_group.' . $consumeGroup);
        // 验证消费组id（MQ的组件必填项）
        if (!isset($cgInfo['group_id']) || empty($cgInfo['group_id'])) {
            throw new MQException('请配置消费组的group_id');
        }

        // 验证消息处理类
        if (empty($cgInfo) || empty($handleClass = $cgInfo['handle_class'] ?? null)) {
            throw new MQException('请定义消息处理类');
        }
        $handleObj = new $handleClass;
        if (!$handleObj instanceof MQConsumerInterface) {
            throw new MQException('处理类必须实现 MQConsumerInterface 接口');
        }

        $this->mqHandleObj = $handleObj;
        $this->groupId = $this->setGroupIdExt($cgInfo['group_id']);
        $this->msgTag = $this->setMsgTagExt($cgInfo['msg_tag'] ?? null);
    }

    /**
     * 设置消费者
     * @author lwz
     */
    private function setConsumer()
    {
        // 获取消费者（这里获取全部 msgTag 的消息）
        $this->consumer = RocketMQClient::getInstance()->getClient()->getConsumer($this->instanceId, $this->topic, $this->groupId, $this->msgTag);
    }

    /**
     * 消费
     * @return mixed
     * @throws MQException
     * @author lwz
     */
    public function consumer()
    {
        while (true) {
            try {
                // 长轮询消费消息
                // 如果topic没有消息则请求会在服务端挂住3s，3s内如果有消息可以消费则立即返回
                $messages = $this->consumer->consumeMessage($this->msgNum, $this->waitSeconds);
            } catch (\Exception $e) {
                if ($e instanceof MessageNotExistException) {
                    // 没有消息可以消费，接着轮询
                    continue;
                }
                sleep(1);
                continue;
            } catch (\Error $err) {
                // mq 获取响应异常
                if ($err->getMessage() == 'Call to undefined method GuzzleHttp\Exception\ConnectException::hasResponse()') {
                    continue;
                }
                throw new $err;
            }

            /**
             * @var $message Message
             */
            // 处理业务逻辑
            foreach ($messages as $message) {
                $msgTag = $message->getMessageTag(); // 消息标签
                $msgKey = $message->getMessageKey(); // 消息唯一标识
                $msgBody = $message->getMessageBody(); // 消息体

                // 格式化消息
                $msgBody = json_decode($msgBody, true);
                try {

                    // 处理消息
                    $this->mqHandleObj->handle($msgBody, $msgKey, $msgTag);

                    // 如果处理消息没有抛出异常，则视为处理成功
                    // 如果配置了消费时删除日志，那么删除发送日志
                    $delSendLogState = $msgBody[MQConst::KEY_DELETE_SEND_LOG_STAGE] ?? null;
                    $delSendLogState == MQConst::DEL_SEND_LOG_CONSUMER && app(MQStatusLogServiceInterface::class)->deleteByMQUuid($msgKey);

                    // 消息确认
                    $this->consumer->ackMessage([$message->getReceiptHandle()]);

                    // 记录日志
                    config('mq.save_consumer_success_log') && Log::info($this->_getLogMsg('[consumer success] 消费信息：', $msgTag, $msgKey, $msgBody));
                } catch (\Throwable $throwable) {
                    // 处理错误
                    $this->_handleError($throwable, $msgKey, $msgBody, $this->_getMqLogConfig());
                    // 消息确认 todo 查看有没有 nack机制，记录消息失败次数
//                    $this->consumer->ackMessage([$message->getReceiptHandle()]);
                    // 记录日志
                    config('mq.save_consumer_error_log') && Log::info($this->_getLogMsg('[consumer error] 消费信息：', $msgTag, $msgKey, $msgBody));
                }
            }
        }
    }

    /**
     * 获取日志消息
     * @param string $mainContent 主消息
     * @param string $msgTag 消息标签
     * @param string $msgKey 消息健
     * @param array $msgBody 消息体
     * @author lwz
     */
    private function _getLogMsg(string $mainContent, string $msgTag, string $msgKey, array $msgBody): string
    {
        return sprintf($mainContent . ' [msg_tag] %s; [msg_key] %s; [msg_body] %s', $msgTag, $msgKey, json_encode($msgBody));
    }

    /**
     * 获取 MQ 日志的配置信息
     * @return array
     * @author lwz
     */
    private function _getMqLogConfig(): array
    {
        return [
            'topic_group' => $this->topic,
            'consume_group' => $this->consumeGroup,
        ];
    }
}
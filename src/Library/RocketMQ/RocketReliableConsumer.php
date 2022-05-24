<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:52,
 * @LastEditTime: 2021/10/27 18:52
 */

namespace Lwz\LaravelExtend\MQ\Library\RocketMQ;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Lwz\LaravelExtend\MQ\Constants\MQConst;
use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use Lwz\LaravelExtend\MQ\Library\MQHelper;
use MQ\Exception\MessageNotExistException;
use MQ\Model\Message;
use MQ\MQConsumer;
use MQ\Exception\AckMessageException;

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
     * 是否保存消息失败日志
     * @var bool
     */
    protected bool $saveErrorLog;

    /**
     * RocketReliableConsumer constructor.
     * @param string $topicGroup topic组
     * @param int $msgNum 每次消费的消息数量(最多可设置为16条)
     * @param int $waitSeconds 长轮询时间（最多可设置为30秒）
     */
    public function __construct(string $topicGroup, string $consumeGroup, int $msgNum = 3, int $waitSeconds = 3)
    {
        // 更改日志驱动
//        Log::setDefaultDriver(config('mq.log_driver'));
        // 设置基本信息
        $this->_setMQInfo($topicGroup);

        // 设置消费组信息
        $this->setConsumeGroupInfo($consumeGroup);

        // 消费相关配置
        $this->topicGroup = $topicGroup;
        $this->consumeGroup = $consumeGroup;
        $this->msgNum = $msgNum;
        $this->waitSeconds = $waitSeconds;

        $this->saveErrorLog = config('mq.save_consumer_error_log');

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
        $this->msgTag = MQHelper::setRocketMQMsgTagExt($cgInfo['msg_tag'] ?? null);
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
                    // Topic中没有消息可消费，继续轮询。
                    continue;
                }

                // 记录错误日志
                $this->saveErrorLog &&
                $this->getLogDriver()->info('获取消息 error:' . $e->getMessage());
                sleep(3);
                continue;
            } /*catch (\Error $err) {
                // mq 获取响应异常
                if ($err->getMessage() == 'Call to undefined method GuzzleHttp\Exception\ConnectException::hasResponse()') {
                    continue;
                }
                throw new $err;
            }*/

            /**
             * @var $message Message
             */
            // 处理业务逻辑
            $receiptHandles = array();
            foreach ($messages as $message) {
                // 消息句柄有时间戳，同一条消息每次消费拿到的都不一样
//                $receiptHandles[] = $message->getReceiptHandle();

                $msgTag = $message->getMessageTag(); // 消息标签
                $msgKey = $message->getMessageKey(); // 消息唯一标识
                $msgBody = $message->getMessageBody(); // 消息体
                $msgId = $message->getMessageId();

                // 格式化消息
                $msgBody = $this->decodeData($msgBody);
                try {

                    // 处理消息
                    // 参数一：获取不到 data 设置为 msgBody，主要为了兼容 之前版本
                    $this->mqHandleObj->handle($msgBody[MQConst::KEY_USER_DATA] ?? $msgBody, $msgKey, $msgTag);

                    // 消息句柄有时间戳，同一条消息每次消费拿到的都不一样
                    $receiptHandles[] = $message->getReceiptHandle();

                    // 如果处理消息没有抛出异常，则视为处理成功
                    // 如果配置了消费时删除日志，那么删除发送日志
                    $delSendLogState = $msgBody[MQConst::KEY_DELETE_SEND_LOG_STAGE] ?? null;
                    $delSendLogState == MQConst::DEL_SEND_LOG_CONSUMER && app(MQStatusLogServiceInterface::class)->deleteByMQUuid($msgKey);

                    // 消息确认
//                    $this->consumer->ackMessage([$message->getReceiptHandle()]);

                    // 记录日志
                    config('mq.save_consumer_success_log')
                    && $this->getLogDriver()->info(
                        $this->_getLogMsg('[consumer success] 消费信息：', $msgId, $msgTag, $msgKey, $msgBody)
                    );

                } catch (\Throwable $throwable) {
                    /*if ($e instanceof MQ\Exception\AckMessageException) {
                        // 某些消息的句柄可能超时了会导致确认不成功
//                        printf("Ack Error, RequestId:%s\n", $e->getRequestId());
                    }*/
                    // 处理错误
                    $this->_handleError($throwable, $msgKey, $msgBody, $this->_getMqLogConfig());
                    // 消息确认 todo 查看有没有 nack机制，记录消息失败次数
//                    $this->consumer->ackMessage([$message->getReceiptHandle()]);
                    // 记录日志
                    $this->saveErrorLog
                    && $this->getLogDriver()->error(
                        $this->_getLogMsg('[consumer error] 消费信息：', $msgId, $msgTag, $msgKey, $msgBody)
                    );

                    // 错误提醒
                    $this->handleSendErrorMsg($throwable);
                }
            }

            try {
                // 消息确认
                $this->consumer->ackMessage($receiptHandles);
            } catch (\Exception $e) {
                if ($e instanceof AckMessageException) {
                    // 某些消息的句柄可能超时，会导致消息消费状态确认不成功。
                    $this->saveErrorLog
                    && $this->getLogDriver()->error("Ack Error, RequestId:%s\n", $e->getRequestId());
                    foreach ($e->getAckMessageErrorItems() as $errorItem) {
                        $this->saveErrorLog
                        && $this->getLogDriver()->error(sprintf("\tReceiptHandle:%s, ErrorCode:%s, ErrorMsg:%s\n", $errorItem->getReceiptHandle(), $errorItem->getErrorCode(), $errorItem->getErrorCode()));
                    }
                }
            }
        }
    }

    /**
     * 发送错误信息
     * @param \Throwable $t 异常对象
     * @author lwz
     */
    protected function handleSendErrorMsg(\Throwable $t): void
    {
        $errMsgMethod = config('mq.sem_method');
        if (empty($errMsgMethod) || !is_array($errMsgMethod)) {
            return;
        }

        $className = $errMsgMethod[0] ?? null;
        $methodName = $errMsgMethod[1] ?? null;
        if (class_exists($className, $methodName)) {

            // 校验异常数据是否已发送
            $errContent = '系统环境：' . env('APP_ENV') . PHP_EOL;
            $errContent .= '错误消息：【队列消费错误】' . $t->getMessage() . PHP_EOL;
            $errContent .= 'trace：' . $t->getTraceAsString() . PHP_EOL;

            // 判断是否限制相同错误发送的间隔
            $cacheKey = null;
            // 检查是否配置了相同错误发送间隔，没有配置直接发送
            if ($errorLimit = config('mq.sem_error_limit')) {
                $cacheKey = 'mq_err_remind_:' . md5($errContent);
                $cacheData = Cache::get('mq_err_remind_:' . md5($errContent));
                // 如果redis数据存在，直接跳过
                if (!is_null($cacheData)) {
                    return;
                }
            }

            (new $className)->$methodName($errContent);

            $errorLimit && Cache::set($cacheKey, '', $errorLimit); // 缓存 60 秒
        }
    }

    /**
     * 获取日志消息
     * @param string $mainContent 主消息
     * @param string $msgId 消息id
     * @param string $msgTag 消息标签
     * @param string $msgKey 消息健
     * @param array $msgBody 消息体
     * @return string
     * @author lwz
     */
    private function _getLogMsg(string $mainContent, string $msgId, string $msgTag, string $msgKey, array $msgBody): string
    {
        return sprintf(
            $mainContent . '[msg_id] %s [msg_tag] %s; [msg_key] %s; [msg_body] %s',
            $msgId, $msgTag, $msgKey, MQHelper::encodeData($msgBody)
        );
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

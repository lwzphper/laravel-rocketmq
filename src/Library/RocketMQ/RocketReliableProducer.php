<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 19:02,
 * @LastEditTime: 2021/10/27 19:02
 */

namespace Lwz\LaravelExtend\MQ\Library\RocketMQ;

use Illuminate\Support\Facades\Log;
use Lwz\LaravelExtend\MQ\Constants\MQConst;
use Lwz\LaravelExtend\MQ\Enum\MQStatusLogEnum;
use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use Lwz\LaravelExtend\MQ\Library\MQHelper;
use Lwz\LaravelExtend\MQ\Traits\ProducerTrait;
use MQ\Model\TopicMessage;

class RocketReliableProducer implements MQReliableProducerInterface
{
    use CommonTrait, ProducerTrait;

    /**
     * 消息生成流程：
     * 1. producePrepare: 提交DB事务时，保存消息状态
     * 2. produce: 事务提交后，发送队列，并更新投递状态
     * tips：消费成功后需要删除消息状态
     */

    /**
     * 消息分组
     * @var string
     */
    protected string $topicGroup;

    /**
     * 消息标签
     * @var string|null
     */
    protected ?string $msgTag;

    /**
     * 延迟时间戳（具体时间的时间戳，如：strotime(2022-10-10 10:32:43)）
     * @var int|null
     */
    protected ?int $delayTime;

    /**
     * mq 消息状态服务应用类
     * @var MQStatusLogServiceInterface
     */
    protected MQStatusLogServiceInterface $mqStatusLogSrvApp;

    /**
     * 队列状态id
     * @var int|null
     */
    protected ?int $mqStatusId = null;

    /**
     * 消息体
     * @var array
     */
    protected array $payload;

    /**
     * RocketProducer constructor.
     * @param string $topicGroup topic所属分组
     * @param string|null $msgTag 消息标签
     * @param string|null $msgKey 消息的 key 。用于唯一标识消息，可以做幂等性处理（如：订单号）
     * @param int|null $delayTime 延迟时间戳（具体时间的时间戳，如：strtotime(2022-10-10 10:32:43)）
     * @param bool $addMsgTagExt 是否添加标签后缀
     */
    public function __construct(string $topicGroup, ?string $msgTag = null, ?string $msgKey = null, ?int $delayTime = null, bool $addMsgTagExt = true)
    {
        // 初始操作
        $this->init();

        // 设置mq基本信息
        $this->_setMQInfo($topicGroup);

        $this->topicGroup = $topicGroup;
        $this->msgTag = $addMsgTagExt ? MQHelper::setRocketMQMsgTagExt($msgTag) : $msgTag;
        $this->msgKey = $msgKey ?: $this->createMsgKey(); // 如果没有设置消息key，自动生成一个唯一标识
        $this->delayTime = $delayTime;
    }

    /**
     * 简单的推送队列（不会记录消息状态，主要用户消息重新投递）
     * @param array $payload
     * @return mixed
     * @throws MQException
     * @author lwz
     */
    public function simplePublish(array $payload): TopicMessage
    {
        $this->payload = $this->packPayload($payload);
        $publishRet = $this->_sendMsg(
            $this->instanceId, $this->topic, $this->payload,
            $this->msgTag, $this->msgKey, $this->delayTime
        );
        // 获取到 消息id 视为投递成功
        $this->handleSendMsgAfter($publishRet);
        return $publishRet;
    }

    /**
     * 发布消息准备（记录消息状态）
     * @param array $payload 消息内容
     * @author lwz
     */
    /*public function publishPrepare(array $payload)
    {
        // 设置状态日志id
        $statusLogRet = $this->mqStatusLogSrvApp->addData($this->msgKey, MQStatusLogEnum::STATUS_WAIT_SEND, $payload, $this->getMqLogConfig());
        $this->mqStatusId = $statusLogRet->id;
        // 设置消息体
        $this->payload = $payload;
    }*/

    /**
     * 发布消息
     * @throws MQException|\Throwable
     * @author lwz
     */
    public function publishMessage(): TopicMessage
    {
        if (!$this->mqStatusId) {
            throw new MQException('需要先调用 publishPrepare 方法');
        }

        try {
            // 发送消息
            $publishRet = $this->_sendMsg(
                $this->instanceId, $this->topic, $this->payload,
                $this->msgTag, $this->msgKey, $this->delayTime
            );
            // 获取到 消息id 视为投递成功
            $this->handleSendMsgAfter($publishRet);
            return $publishRet;
        } catch (\Throwable $t) {
            self::_handleError($t, $this->msgKey, $this->payload, $this->getMqLogConfig());
            throw $t;
        }
    }


    /**
     * 处理发送消息后的操作
     * @param TopicMessage $publishRet
     * @author lwz
     */
    protected function handleSendMsgAfter(TopicMessage $publishRet)
    {
        // 获取到 消息id 视为投递成功
        if ($this->_checkIsProduceSuccess($publishRet)) {
            // 根据删除发送日志阶段，做相应处理
            $logState = $this->payload[MQConst::KEY_DELETE_SEND_LOG_STAGE] ?? null;
            if ($logState == MQConst::DEL_SEND_LOG_MSG_ID) { // 接收到 消息id 删除日志
                app(MQStatusLogServiceInterface::class)->deleteByMQUuid($this->msgKey);
            } else { // 消费时删除日志，更新消息发送状态
                // 更新消息投递状态
                $this->mqStatusLogSrvApp->updateStatusByMQUuId(
                    $this->msgKey,
                    MQStatusLogEnum::STATUS_WAIT_CONSUME,
                    date('Y-m-d H:i:s', $this->delayTime ?: time())
                );
            }
        }
    }
}

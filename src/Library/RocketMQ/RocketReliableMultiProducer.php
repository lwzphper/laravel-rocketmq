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

/**
 * Class RocketReliableMultiProducer
 * @package Lwz\LaravelExtend\MQ\Library\RocketMQ
 * @author lwz
 * 批量发送，全部推送的数据，都在 payload 里面
 */
class RocketReliableMultiProducer implements MQReliableProducerInterface
{
    use CommonTrait, ProducerTrait;

    /**
     * mq 消息状态服务应用类
     * @var MQStatusLogServiceInterface
     */
    protected MQStatusLogServiceInterface $mqStatusLogSrvApp;

    /**
     * 是否保存了队列状态
     * @var bool
     */
    protected bool $isSaveMqStatus = false;

    /**
     * 推送的数据
     * @var array
     */
    protected array $publishData = [];

    public function __construct()
    {
        $this->init();
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
        // 由于遍历发送消息，存在网络异常问题，可能导致部分发送成功，部分失败。
        // 而重新投递时，msg_key 会更新，导致相同的数据，消费端无法做幂等处理
        // 除非，msg_key 由用户自己规定
        throw new MQException('simplePublish 暂不支持，一次发送多条消息');
    }

    /**
     * 发布消息准备（记录消息状态）
     * @param array $payload 消息内容
     * @author lwz
     */
    public function publishPrepare(array $payload)
    {
        $result = [];
        $logData = []; // 日志数据
        foreach ($payload as $item) {
            // 检查数据结构是否正确。
            if (!isset($item[Constant::FIELD_TOPIC_GROUP]) || !isset($item[Constant::FIELD_TAG]) || !isset($item[Constant::FIELD_PAYLOAD])) {
                throw new MQException('数据缺少必填字段');
            }

            $item[Constant::FIELD_MSG_KEY] = $item[Constant::FIELD_MSG_KEY] ?? $this->createMsgKey();
            $item[Constant::FIELD_PAYLOAD] = $this->packPayload($item[Constant::FIELD_PAYLOAD]);
            $item[Constant::FIELD_TAG] = MQHelper::setRocketMQMsgTagExt($item[Constant::FIELD_TAG]);
            $item[Constant::FIELD_MQ_CONFIG] = $this->getMqLogConfig(
                $item[Constant::FIELD_TOPIC_GROUP], $item[Constant::FIELD_TAG],
                $item[Constant::FIELD_MSG_KEY], $item[Constant::FIELD_DELAY_TIME] ?? null
            );

            // 设置日志数据
            array_push($logData, [
                'mq_uuid' => $item[Constant::FIELD_MSG_KEY],
                'payload' => $item[Constant::FIELD_PAYLOAD],
                'mq_config' => $item[Constant::FIELD_MQ_CONFIG],
            ]);
            // 设置结果数据
            array_push($result, $item);
        }

        // 记录日志状态
        $this->mqStatusLogSrvApp->addMultiWaitSend($logData);

        $this->isSaveMqStatus = true;
        // 设置消息体
        $this->publishData = $result;
    }

    /**
     * 发布消息
     * @throws MQException|\Throwable
     * @author lwz
     */
    public function publishMessage()
    {
        if (!$this->isSaveMqStatus) {
            throw new MQException('需要先调用 publishPrepare 方法');
        }

        $successData = []; // 记录成功投递的消息标签id。格式： 消息id => 延迟时间（主要用于更新日志的更新时间）

        foreach ($this->publishData as $data) {
            try {

                list($instanceId, $topic) = $this->getTopicInfoOrFail($data[Constant::FIELD_TOPIC_GROUP]);
                $delayTime = $data[Constant::FIELD_DELAY_TIME] ?? null;

                // 发送消息
                $publishRet = $this->_sendMsg(
                    $instanceId, $topic, $data[Constant::FIELD_PAYLOAD],
                    $data[Constant::FIELD_TAG], $data[Constant::FIELD_MSG_KEY], $delayTime
                );

                // 记录推送成功的结果
                if ($this->_checkIsProduceSuccess($publishRet)) {
                    $successData[$data[Constant::FIELD_MSG_KEY]] = $data[Constant::FIELD_DELAY_TIME] ?? null;
                }

            } catch (\Throwable $t) {
                // 记录推送失败的错误
                self::_handleError(
                    $t, $data[Constant::FIELD_MSG_KEY],
                    $data[Constant::FIELD_PAYLOAD], $data[Constant::FIELD_MQ_CONFIG]
                );
            }
        }

        // 处理推送成功的操作
        $this->handleSendSuccessMsg($successData);
    }


    /**
     * 处理发送成功的消息
     * @param array $successData 成功投递的数据。格式： 消息id => 延迟时间（主要用于更新日志的更新时间）
     * @author lwz
     */
    protected function handleSendSuccessMsg(array $successData)
    {
        if (empty($successData)) {
            return;
        }

        // 根据删除发送日志阶段，做相应处理（目前只从配置文件里面提取，全局一样）
        $logState = config('mq.delete_send_log_stage');
        if ($logState == MQConst::DEL_SEND_LOG_MSG_ID) { // 接收到 消息id 删除日志
            app(MQStatusLogServiceInterface::class)->deleteByMQUuid(array_keys($successData));
        } else { // 消费时删除日志，更新消息发送状态
            foreach ($successData as $msgKey => $delayTime) {
                // 更新消息投递状态
                $this->mqStatusLogSrvApp->updateStatusByMQUuId(
                    $msgKey,
                    MQStatusLogEnum::STATUS_WAIT_CONSUME,
                    date('Y-m-d H:i:s', $delayTime ?: time())
                );
            }
        }
    }
}

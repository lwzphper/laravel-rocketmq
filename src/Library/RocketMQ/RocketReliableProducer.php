<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 19:02,
 * @LastEditTime: 2021/10/27 19:02
 */

namespace Lwz\LaravelExtend\MQ\Library\RocketMQ;


use Illuminate\Support\Facades\Log;
use Lwz\LaravelExtend\MQ\Enum\MQStatusLogEnum;
use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use MQ\Model\TopicMessage;

class RocketReliableProducer implements MQReliableProducerInterface
{
    use CommonTrait;
    /**
     * 消息生成流程：
     * 1. producePrepare: 提交DB事务时，保存消息状态
     * 2. produce: 事务提交后，发送队列，并更新投递状态
     * tips：消费成功后需要删除消息状态
     */

    /**
     * 消息标签。项目中使用配置文件的 routes key 作为 消息标签
     * @var string
     */
    protected string $msgTag;

    /**
     * 消息的 key 。用于唯一标识消息，可以做幂等性处理（如：订单号）
     * @var string|null
     */
    protected ?string $msgKey;

    /**
     * 延迟时间戳（具体时间的时间戳，如：strotime(2022-10-10 10:32:43)）
     * @var int|null
     */
    protected ?int $delayTime;

    /**
     * 配置文件的分组名（包含：topic、实例、分组id）
     * @var string
     */
    protected string $configGroupName;

    /**
     * mq 消息状态服务应用类
     * @var MQStatusLogServiceInterface
     */
    protected string $mqStatusLogSrvApp;

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
     * @param string $msgTag 消息标签。项目中使用配置文件的 routes key 作为 消息标签
     *  （tips：Topic与Tag都是业务上用来归类的标识，区分在于Topic是一级分类，而Tag可以理解为是二级分类）
     * @param string $configGroupName 配置文件的分组名（包含：topic、实例、分组id）
     * @param string|null $msgKey 消息的 key 。用于唯一标识消息，可以做幂等性处理（如：订单号）
     * @param int|null $delayTime 延迟时间戳（具体时间的时间戳，如：strotime(2022-10-10 10:32:43)）
     */
    public function __construct(string $msgTag, string $configGroupName, ?string $msgKey = null, ?int $delayTime = null)
    {
        // 更改日志驱动
        Log::setDefaultDriver('queueLog');

        $this->msgTag = $msgTag;
        $this->msgKey = $msgKey ?: session_create_id('mq'); // 如果没有设置消息key，自动生成一个唯一标识
        $this->delayTime = $delayTime;
        $this->configGroupName = $configGroupName;
        // 设置应用类
        $this->mqStatusLogSrvApp = app(MQStatusLogServiceInterface::class);
    }

    /**
     * 简单的推送队列（不会记录消息状态，主要用户消息重新投递）
     * @param array $payload
     * @return mixed
     * @author lwz
     */
    public function simplePublish(array $payload)
    {
        $this->payload = $payload;
        $this->_sendMsg();
    }

    /**
     * 发布消息准备（记录消息状态）
     * @param array $payload 消息内容
     * @author lwz
     */
    public function publishPrepare(array $payload)
    {
        // 设置状态日志id
        $statusLogRet = $this->mqStatusLogSrvApp->addData($this->msgKey, MQStatusLogEnum::STATUS_WAIT_SEND, $payload, $this->_getMqLogConfig());
        $this->mqStatusId = $statusLogRet->id;
        // 设置消息体
        $this->payload = $payload;
    }

    /**
     * 发布消息
     * @throws MQException
     * @author lwz
     */
    public function publishMessage()
    {
        if (!$this->mqStatusId) {
            throw new MQException('需要先调用 publishPrepare 方法');
        }

        try {
            // 发送消息
            $publishRet = $this->_sendMsg();
            // 获取到 消息id 视为投递成功
            if (isset($publishRet->messageId) && !empty($publishRet->messageId)) {
                // 更新消息投递状态
                $this->mqStatusLogSrvApp->updateStatusByMQUuId($this->msgKey, MQStatusLogEnum::STATUS_WAIT_CONSUME);
            }
        } catch (\Throwable $t) {
            self::_handleError($t, $this->msgKey, $this->payload, $this->_getMqLogConfig());
        }
    }

    /**
     * 发送消息
     * @return TopicMessage
     * @author lwz
     */
    private function _sendMsg(): TopicMessage
    {
        // 获取配置信息
        $config = config('mq.rocketmq.group.' . $this->configGroupName);
        // 获取生产者
        $producer = RocketMQClient::getInstance()->getClient()->getProducer($config['instance_id'], $config['topic']);

        // 发布消息
        $payload = json_encode($this->payload);
        $publishMessage = new TopicMessage($payload); //消息内容
        $publishMessage->setMessageTag($this->msgTag);//设置TAG
        // 设置消息KEY
        $publishMessage->setMessageKey($this->msgKey);
        // 延迟时间
        if ($this->delayTime) {
            $publishMessage->setStartDeliverTime($this->delayTime * 1000);
        }

        $publishRet = $producer->publishMessage($publishMessage);
        // 记录日志
        Log::info(sprintf('消息生产成功。[msg_tag] %s; [msg_key] %s; [msg_body] %s', $this->msgTag, $this->msgKey, $payload));

        return $publishRet;
    }

    /**
     * 获取 MQ 日志的配置信息
     * @return array
     * @author lwz
     */
    private function _getMqLogConfig(): array
    {
        return [
            'msg_tag' => $this->msgTag,
            'delay_time' => $this->delayTime,
            'config_group' => $this->configGroupName,
        ];
    }
}
<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:52,
 * @LastEditTime: 2021/10/27 18:52
 */

namespace Lwz\LaravelExtend\MQ\Library\RocketMQ;


use Illuminate\Support\Facades\Log;
use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use MQ\Exception\MessageNotExistException;
use MQ\Model\Message;
use MQ\MQConsumer;

class RocketReliableConsumer implements MQReliableConsumerInterface
{
    use CommonTrait;

    const MSG_TAG_CLASS_NOT_FOUND_CODE = 4001; // 消息标签对应的处理类不存在
    const MSG_CALLBACK_ERROR_CODE = 4002; // 消息处理类的回调函数不存在

    /**
     * 配置文件的分组名（包含：topic、实例、分组id）
     * @var string
     */
    protected string $configGroupName;

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
     * mq 消息状态服务应用类
     * @var MQStatusLogServiceInterface
     */
    protected string $mqStatusLogSrvApp;

    /**
     * message tag 对应的处理类
     * @var array
     */
    protected array $msgTagHandleClass;

    /**
     * 消费者对象
     * @var MQConsumer
     */
    protected MQConsumer $consumer;

    /**
     * RocketReliableConsumer constructor.
     * @param string $configGroupName 配置文件的分组名（包含：topic、实例、分组id）
     * @param int $msgNum 每次消费的消息数量(最多可设置为16条)
     * @param int $waitSeconds 长轮询时间（最多可设置为30秒）
     */
    public function __construct(string $configGroupName, int $msgNum = 3, int $waitSeconds = 3)
    {
        // 更改日志驱动
        Log::setDefaultDriver('queueLog');

        $this->configGroupName = $configGroupName;
        $this->msgNum = $msgNum;
        $this->waitSeconds = $waitSeconds;

        // 配置文件选项
        $this->mqStatusLogSrvApp = config('mq.service_app.status_log');
        $this->msgTagHandleClass = config('rocketmq.routes');

        $this->setConsumer();
    }

    /**
     * 设置消费者
     * @author lwz
     */
    private function setConsumer()
    {
        // 获取配置信息
        $config = config('rocketmq.group.' . $this->configGroupName);
        // 获取消费者（这里获取全部 msgTag 的消息）
        $this->consumer = RocketMQClient::getInstance()->getClient()->getConsumer($config['instance_id'], $config['topic'], $config['group_id']);
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
                $msgTag = $message->getMessageTag();
                $msgKey = $message->getMessageKey();
                $msgBody = $message->getMessageBody();

                try {
                    // 处理消息
                    $this->_getMsgTagHandleObjOrFail($msgTag)->callbacks($msgBody, $msgKey);

                    // 如果处理消息没有抛出异常，则视为处理成功，处理成功删除消息状态记录
                    $msgKey && app($this->mqStatusLogSrvApp)->deleteByMQUuid($msgKey);

                    // 消息确认
                    $this->consumer->ackMessage([$message->getReceiptHandle()]);

                    // 记录日志
                    Log::info($this->_getLogMsg('[success] 消费信息：', $msgTag, $msgKey, $msgBody));
                } catch (\Throwable $throwable) {
                    // 处理错误
                    $this->_handleError($throwable, $msgKey, $msgBody, $this->_getMqLogConfig($msgTag));
                    // 消息确认。由于守护进程会定时监听 消息状态表 进行重试，因此不需要再这里重试
                    // 只有 消息key 存在，才确认消息（为了兼容之前的代码）
                    $msgKey && $this->consumer->ackMessage([$message->getReceiptHandle()]);
                    // 记录日志
                    Log::info($this->_getLogMsg('[error] 消费信息：', $msgTag, $msgKey, $msgBody));
                }
            }
        }
    }

    /**
     * 获取日志消息
     * @param string $mainContent 主消息
     * @param string $msgTag 消息标签
     * @param string $msgKey 消息健
     * @param string $msgBody 消息体
     * @author lwz
     */
    private function _getLogMsg(string $mainContent, string $msgTag, string $msgKey, string $msgBody): string
    {
        return sprintf($mainContent . ' [msg_tag] %s; [msg_key] %s; [msg_body] %s', $msgTag, $msgKey, $msgBody);
    }

    /**
     * 获取 MQ 日志的配置信息
     * @param string $msgTag 消息标签
     * @return array
     * @author lwz
     */
    private function _getMqLogConfig(string $msgTag): array
    {
        return [
            'msg_tag' => $msgTag,
            'config_group' => $this->configGroupName,
        ];
    }

    /**
     * 获取消息标签对应的处理类
     * @param string $msgTag
     * @return mixed
     * @throws MQException
     * @author lwz
     */
    private function _getMsgTagHandleObjOrFail(string $msgTag)
    {
        $class = $this->msgTagHandleClass[$msgTag] ?? null;
        // 判断有没有 消息标签 的处理类
        if (!$class) {
            throw new MQException('[' . $msgTag . ']消息标签 对应的处理类不存在', self::MSG_TAG_CLASS_NOT_FOUND_CODE);
        }
        // 判断 处理类 有没有实现 callbacks 方法
        $obj = new $class;
        if (method_exists($obj, 'callbacks')) {
            throw new MQException('[' . get_class($obj) . '] 消息处理类必须实现 callbacks 方法', self::MSG_CALLBACK_ERROR_CODE);
        }
        return $obj;
    }
}
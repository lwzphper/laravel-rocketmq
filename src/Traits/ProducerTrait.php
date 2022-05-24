<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/12/1 23:15,
 * @LastEditTime: 2021/12/1 23:15
 */
declare(strict_types=1);

namespace Lwz\LaravelExtend\MQ\Traits;

use Illuminate\Support\Facades\Log;
use Lwz\LaravelExtend\MQ\Constants\MQConst;
use Lwz\LaravelExtend\MQ\Enum\MQStatusLogEnum;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use Lwz\LaravelExtend\MQ\Library\MQHelper;

trait ProducerTrait
{
    /**
     * 消息的 key 。用于唯一标识消息，可以做幂等性处理（如：订单号）
     * @var string|null
     */
    protected ?string $msgKey = null;

    /**
     * mq 消息状态服务应用类
     * @var MQStatusLogServiceInterface
     */
    protected MQStatusLogServiceInterface $mqStatusLogSrvApp;

    /**
     * 发布消息准备（记录消息状态）
     * @param array $payload 消息内容
     * @author lwz
     */
    public function publishPrepare(array $payload)
    {
        $result = $this->packPayload($payload);
        // 设置状态日志id
        $statusLogRet = $this->mqStatusLogSrvApp->addData($this->msgKey, MQStatusLogEnum::STATUS_WAIT_SEND, $result, $this->getMqLogConfig());
        $this->mqStatusId = $statusLogRet->id;
        // 设置消息体
        $this->payload = $result;
    }

    /**
     * 打包消息内容
     * @param array $payload 消息内容
     * @return array
     * @author lwz
     */
    protected function packPayload(array $payload): array
    {
        $result = [];
        $result[MQConst::KEY_USER_DATA] = $payload;
        // 负载数据加上删除发送日志的时机
        $result[MQConst::KEY_DELETE_SEND_LOG_STAGE] = config('mq.delete_send_log_stage');
        return $result;
    }

    /**
     * 创建消息key（唯一标识）
     * @return string
     */
    protected function createMsgKey(): string
    {
        return session_create_id('mq');
    }

    /**
     * 初始化操作
     */
    protected function init()
    {
        // 设置日志驱动 （生产者不需要设置日志驱动）
//        Log::setDefaultDriver(config('mq.log_driver'));

        // 设置 mq 消息状态服务应用类
        $this->mqStatusLogSrvApp = app(MQStatusLogServiceInterface::class);
    }
}

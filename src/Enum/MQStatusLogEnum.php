<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/11 16:17,
 * @LastEditTime: 2021/10/11 16:17
 */

namespace Lwz\LaravelExtend\MQ\Enum;

/**
 * Class MqStatusLogEnum
 * @package App\Common\Enums\Queue
 * @author lwz
 * 队列状态日志
 */
class MQStatusLogEnum
{
    // 状态值
    const STATUS_WAIT_SEND = 1; // 待发送
    const STATUS_WAIT_CONSUME = 2; // 待消费（已发送到broker）
}

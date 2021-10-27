<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 17:55,
 * @LastEditTime: 2021/10/27 17:55
 */

namespace Lwz\LaravelExtend\MQ\Interfaces;

/**
 * Interface MQReliableInterface
 * @package App\Common\Library\MQ
 * @auth lwz
 * MQ 可靠投递接口
 */
interface MQReliableConsumerInterface
{
    /**
     * 消费
     * @return mixed
     * @author lwz
     */
    public function consumer();
}
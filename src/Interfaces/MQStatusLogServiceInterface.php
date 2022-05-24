<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:35,
 * @LastEditTime: 2021/10/27 18:35
 */

namespace Lwz\LaravelExtend\MQ\Interfaces;


use Illuminate\Support\Collection;
use Lwz\LaravelExtend\MQ\Models\MQStatusLog;

interface MQStatusLogServiceInterface
{
    /**
     * 添加
     * @param string $mqUuid mq唯一标识
     * @param int $status 消息状态
     * @param array $payload 负载，消息体
     * @param array $mqConfig mq配置
     * @return mixed
     */
    public function addData(string $mqUuid, int $status, array $payload, array $mqConfig): MQStatusLog;

    /**
     * 批量添加待发送状态数据
     * @param array $data 数据
     *    mq_uuid: 唯一标识
     *    mq_config：队列配置
     *    payload: 消息体
     * @return mixed
     * @author lwz
     */
    public function addMultiWaitSend(array $data);

    /**
     * 通过id更新数据
     * @param array $ids id
     * @return mixed
     * @author lwz
     */
    public function updateReproduceData(array $ids);

    /**
     * 更新消息状态
     * @param string|array $mqUuid mq唯一标识（多个传数组）
     * @param int $status 消息状态
     * @param string|null $updateTime 更新时间（防止延迟队列，消息没有到达指定时间点，被重复投递）
     * @return mixed
     * @author lwz
     */
    public function updateStatusByMQUuId($mqUuid, int $status, ?string $updateTime = null);

    /**
     * 通过 uuid 删除消息
     * @param string|array $mqUuid mq唯一标识（多个传数组）
     * @return mixed
     * @auth lwz
     */
    public function deleteByMQUuid($mqUuid);

    /**
     * 获取需要重新投递的数据
     * @param int $num 获取的数量
     * @return mixed
     * @author lwz
     */
    public function getReproduceData(int $num): Collection;

    /**
     * 通过id批量删除
     * @param array $ids id数组
     * @return mixed
     * @author lwz
     */
    public function deleteByIds(array $ids);
}

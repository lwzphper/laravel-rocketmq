<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:35,
 * @LastEditTime: 2021/10/27 18:35
 */

namespace Lwz\LaravelExtend\MQ\Services;


use Illuminate\Support\Collection;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use Lwz\LaravelExtend\MQ\Models\MQStatusLog;
use Lwz\LaravelExtend\MQ\Repositories\MQStatusLogRepository;

class MQStatusLogService implements MQStatusLogServiceInterface
{
    /**
     * 添加
     * @param string $mqUuid mq唯一标识
     * @param int $status 消息状态
     * @param array $payload 负载，消息体
     * @param array $mqConfig mq配置
     * @return mixed
     */
    public function addData(string $mqUuid, int $status, array $payload, array $mqConfig): MQStatusLog
    {
        return MQStatusLogRepository::add([
            'status' => $status,
            'mq_uuid' => $mqUuid,
            'mq_config' => json_encode($mqConfig, true),
            'payload' => json_encode($payload, true),
        ]);
    }

    /**
     * 更新消息状态
     * @param string $mqUuid mq唯一标识
     * @param int $status 消息状态
     * @param string|null $updateTime 更新时间（防止延迟队列，消息没有到达指定时间点，被重复投递）
     * @return mixed
     * @author lwz
     */
    public function updateStatusByMQUuId(string $mqUuid, int $status, ?string $updateTime = null)
    {
        $updateData = compact('status');
        $updateTime && $updateData['updated_at'] = $updateTime;
        return MQStatusLogRepository::updateByWhere(['mq_uuid' => $mqUuid], $updateData);
    }

    /**
     * 通过 uuid 删除消息
     * @param string $mqUuid mq唯一标识
     * @return mixed
     * @auth lwz
     */
    public function deleteByMQUuid(string $mqUuid)
    {
        return MQStatusLogRepository::deleteByWhere(['mq_uuid' => $mqUuid]);
    }

    /**
     * 获取需要重新投递的数据
     * @param int $num 获取的数量
     * @return mixed
     * @author lwz
     */
    public function getReproduceData(int $num): Collection
    {
        return MQStatusLogRepository::getReproduceData($num);
    }

    /**
     * 通过id更新数据
     * @param array $ids id
     * @return mixed
     * @author lwz
     */
    public function updateReproduceData(array $ids)
    {
        return MQStatusLogRepository::updateReproduceData($ids);
    }
}
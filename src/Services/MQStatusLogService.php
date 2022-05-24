<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:35,
 * @LastEditTime: 2021/10/27 18:35
 */

namespace Lwz\LaravelExtend\MQ\Services;


use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use Lwz\LaravelExtend\MQ\Models\MQStatusLog;
use Lwz\LaravelExtend\MQ\Repositories\MQStatusLogRepository;
use Lwz\LaravelExtend\MQ\Library\MQHelper;

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
            'mq_config' => MQHelper::encodeData($mqConfig),
            'payload' => MQHelper::encodeData($payload),
        ]);
    }

    /**
     * 批量添加待发送状态数据
     * @param array $data 数据
     *    mq_uuid: 唯一标识
     *    mq_config：队列配置
     *    payload: 消息体
     * @return mixed
     * @author lwz
     */
    public function addMultiWaitSend(array $data)
    {
        return MQStatusLogRepository::insert(array_map(function ($item) {
            $item = Arr::only($item, ['mq_uuid', 'mq_config', 'payload']);
            $item['mq_config'] = MQHelper::encodeData($item['mq_config']);
            $item['payload'] = MQHelper::encodeData($item['payload']);
            return $item;
        }, $data));
    }

    /**
     * 更新消息状态
     * @param string|array $mqUuid mq唯一标识（多个传数组）
     * @param int $status 消息状态
     * @param string|null $updateTime 更新时间（防止延迟队列，消息没有到达指定时间点，被重复投递）
     * @return mixed
     * @author lwz
     */
    public function updateStatusByMQUuId($mqUuid, int $status, ?string $updateTime = null)
    {
        $updateData = compact('status');
        $updateTime && $updateData['updated_at'] = $updateTime;
        return MQStatusLogRepository::updateByWhere(['mq_uuid' => $mqUuid], $updateData);
    }


    /**
     * 通过 uuid 删除消息
     * @param string|array $mqUuid mq唯一标识（多个传数组）
     * @return mixed
     * @auth lwz
     */
    public function deleteByMQUuid($mqUuid)
    {
        return MQStatusLogRepository::deleteByMQUuid($mqUuid);
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

    /**
     * 通过id批量删除
     * @param array $ids id数组
     * @return mixed
     * @author lwz
     */
    public function deleteByIds(array $ids)
    {
        return MQStatusLogRepository::deleteByIds($ids);
    }
}

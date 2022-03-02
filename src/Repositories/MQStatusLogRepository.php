<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:38,
 * @LastEditTime: 2021/10/27 18:38
 */

namespace Lwz\LaravelExtend\MQ\Repositories;


use Illuminate\Support\Facades\DB;
use Lwz\LaravelExtend\MQ\Enum\MQStatusLogEnum;
use Lwz\LaravelExtend\MQ\Models\MQStatusLog;

class MQStatusLogRepository extends RepositoryAbstract
{
    protected static string $model = MQStatusLog::class;
    /**
     * 通过id更新数据
     * @param array $ids id
     * @return mixed
     * @author lwz
     */
    public static function updateReproduceData(array $ids)
    {
        return MQStatusLog::whereIn('id', $ids)->update([
            'status' => MQStatusLogEnum::STATUS_WAIT_CONSUME,
            'retry_num' => DB::raw('retry_num + 1'),
        ]);
    }

    /**
     * 获取需要重新投递的数据
     * @param int $num 获取的数量
     * @return mixed
     * @author lwz
     */
    public static function getReproduceData(int $num)
    {
        // 获取相关配置
        $maxNum = config('mq.reproduce_max_num');
        $gtSeconds = config('mq.reproduce_time');

        return MQStatusLog::select(['id', 'mq_uuid', 'status', 'mq_config', 'payload'])
            ->where('retry_num', '<', $maxNum)
            ->where('updated_at', '<', date('Y-m-d H:i:s', time() - $gtSeconds))
            ->limit($num)
            ->get();
    }

    /**
     * 通过id批量删除
     * @param array $ids
     * @return mixed
     * @author lwz
     */
    public static function deleteByIds(array $ids)
    {
        return MQStatusLog::whereIn('id', $ids)->delete();
    }
}
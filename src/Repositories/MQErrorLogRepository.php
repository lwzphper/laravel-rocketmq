<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:38,
 * @LastEditTime: 2021/10/27 18:38
 */

namespace Lwz\LaravelExtend\MQ\Repositories;


use Lwz\LaravelExtend\MQ\Models\MQErrorLog;

class MQErrorLogRepository extends RepositoryAbstract
{
    protected static string $model = MQErrorLog::class;
}
<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:23,
 * @LastEditTime: 2021/10/27 18:23
 */

namespace Lwz\LaravelExtend\MQ\Models;


use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $guarded = []; // 黑名单

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
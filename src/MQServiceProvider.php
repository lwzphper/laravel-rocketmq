<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 17:51,
 * @LastEditTime: 2021/10/27 17:51
 */

namespace Lwz\LaravelExtend\MQ;


use Illuminate\Support\ServiceProvider;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;
use Lwz\LaravelExtend\MQ\Library\RocketMQ\RocketReliableConsumer;
use Lwz\LaravelExtend\MQ\Library\RocketMQ\RocketReliableProducer;

class MQServiceProvider extends ServiceProvider
{
    public function register()
    {
        // 注册队列
        $this->_registerMQ();

        // todo 注册配置

        // todo 注册消费命令

        // 注册 生成、消费 者
    }

    /**
     * 系统服务注册
     * @author lwz
     */
    private function _registerMQ()
    {
        // 队列生产者注册
        $this->app->bind(MQReliableProducerInterface::class, function ($app, array $params = []) {
            /**
             * @var $params
             * msg_tag: 消息标签
             * msg_key: 消息唯一标识（不传，会自动生成一个唯一标识）
             * config_group: 配置文件中的 分组名
             * delay_time: 延迟时间戳（具体时间的时间戳，如：strotime(2022-10-10 10:32:43)）
             */
            return new RocketReliableProducer(
                $params['msg_tag'],
                $params['config_group'],
                $params['msg_key'] ?? null,
                $params['delay_time'] ?? null
            );
        });

        // 队列消费者注册
        $this->app->bind(MQReliableConsumerInterface::class, function ($app, array $params = []) {
            /**
             * @var $params
             * config_group: 配置文件中的 分组名
             * msg_num: 每次消费的消息数量(最多可设置为16条)
             * wait_seconds: 长轮询时间（最多可设置为30秒）
             */
            return new RocketReliableConsumer(
                $params['config_group'],
                $params['msg_num'] ?? 3,
                $params['wait_seconds'] ?? 3,
            );
        });
    }
}
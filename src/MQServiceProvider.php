<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 17:51,
 * @LastEditTime: 2021/10/27 17:51
 */

namespace Lwz\LaravelExtend\MQ;

use Closure;
use Illuminate\Support\ServiceProvider;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;
use Lwz\LaravelExtend\MQ\Library\MQProducer;

class MQServiceProvider extends ServiceProvider
{

    public function register()
    {
        // 注册配置
        $this->_loadConfig();

        // 注册 迁移文件
        $this->_loadMigrations();

        // 注册队列
        $this->_registerMQ();

        // 发布配置文件
        $this->registerPublishing();
    }

    /**
     * 系统服务注册
     * @author lwz
     */
    private function _registerMQ()
    {
        // 队列生产者注册
        $this->app->bind(MQReliableProducerInterface::class, function ($app, array $params = []) {
            return MQProducer::getProducer($params);
        });

        // 队列消费者注册
        $this->app->bind(MQReliableConsumerInterface::class, function ($app, array $params = []) {
            return MQProducer::getConsumer($params);
        });
    }

    /**
     * 加载配置文件
     * @author lwz
     */
    private function _loadConfig()
    {
        // mq 队列配置
        $this->mergeConfigFrom(__DIR__ . '/Config/mq.php', 'mq');
        // RocketMQ 配置
        $this->mergeConfigFrom(__DIR__ . '/Config/rocketmq.php', 'rocketmq');
    }

    /**
     * 注册迁移文件
     * @author lwz
     */
    private function _loadMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');
        }
    }


    /**
     * 发布模板文件
     * @author lwz
     */
    protected function registerPublishing()
    {
        // 只有在 console 模式才执行
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config' => $this->app->configPath()
            ]);
        }
    }
}
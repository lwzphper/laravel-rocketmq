<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 17:51,
 * @LastEditTime: 2021/10/27 17:51
 */

namespace Lwz\LaravelExtend\MQ;

use Illuminate\Support\ServiceProvider;
use Lwz\LaravelExtend\MQ\Commands\MQConsumer;
use Lwz\LaravelExtend\MQ\Commands\MQReproduceFailMsg;
use Lwz\LaravelExtend\MQ\Constants\MQConst;
use Lwz\LaravelExtend\MQ\Interfaces\MQErrorLogServiceInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use Lwz\LaravelExtend\MQ\Library\MQProducer;
use Lwz\LaravelExtend\MQ\Services\MQErrorLogService;
use Lwz\LaravelExtend\MQ\Services\MQStatusLogService;

class MQServiceProvider extends ServiceProvider
{
    protected array $command = [
        MQReproduceFailMsg::class,
        MQConsumer::class,
    ];

    public function register()
    {
        // 注册配置
        $this->_loadConfig();

        // 注册 迁移文件
        $this->_loadMigrations();

        // 服务注册
        $this->_registerService();

        // 发布配置文件
        $this->registerPublishing();

        // 注册命令
        $this->commands($this->command);
    }

    /**
     * 服务注册
     * @author lwz
     */
    private function _registerService()
    {
        // 队列生产者注册
        $this->app->bind(MQReliableProducerInterface::class, function ($app, array $params = []) {
            // 补上删除发送日志的阶段
            $params[MQConst::KEY_DELETE_SEND_LOG_STAGE] = config('mq.delete_send_log_stage');
            return MQProducer::getProducer($params);
        });

        // 队列消费者注册
        $this->app->bind(MQReliableConsumerInterface::class, function ($app, array $params = []) {
            return MQProducer::getConsumer($params);
        });

        // 消息状态
        $this->app->instance(MQStatusLogServiceInterface::class, $this->app->make(MQStatusLogService::class));
        // 错误日志
        $this->app->instance(MQErrorLogServiceInterface::class, $this->app->make(MQErrorLogService::class));
    }

    /**
     * 加载配置文件
     * @author lwz
     */
    private function _loadConfig()
    {
        // mq 队列配置
        $this->mergeConfigFrom(__DIR__ . '/Config/mq.php', 'mq');
        // 日志
        $this->mergeConfigFrom(__DIR__ . '/Config/logging.php', 'logging.channels');
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
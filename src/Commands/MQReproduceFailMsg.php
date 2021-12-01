<?php

namespace Lwz\LaravelExtend\MQ\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Lwz\LaravelExtend\MQ\Exceptions\MQException;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;
use Lwz\LaravelExtend\MQ\Interfaces\MQStatusLogServiceInterface;
use Lwz\LaravelExtend\MQ\Library\RocketMQ\CommonTrait;

class MQReproduceFailMsg extends Command
{
    use CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mq:reproduce';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重试没有成功消费的消息（mq_status_log 表）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mqStatusLogApp = app(MQStatusLogServiceInterface::class);
        while (true) {
            /**
             * @var $collection Collection
             */
            // 获取重试失败的数量
            $collection = $mqStatusLogApp->getReproduceData(30);
            if ($collection->isEmpty()) {
                sleep(5);
                continue;
            }
            // 重新投递
            $collection->each(function ($item) {
                try {

                    // 获取配置信息
                    $config = json_decode($item->mq_config, true);
                    app(MQReliableProducerInterface::class, $config)->simplePublish(json_decode($item->payload, true));
                } catch (MQException $exception) {
                    // 捕获异常。如果这里记录日志，如果有异常的话，会不断写入日志文件，导致文件很大
                    // 所以这里不做任何处理
                }
            });

            // 更新日志状态（没有更新成功也更新，否则一直会获取投递失败的消息）
            $mqStatusLogApp->updateReproduceData($collection->pluck('id')->toArray());
            sleep(1); // 暂停1秒
        }
    }
}

<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/12/1 22:49,
 * @LastEditTime: 2021/12/1 22:49
 */
declare(strict_types=1);

use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;

class consumer
{
    public function handle()
    {
        app(MQReliableConsumerInterface::class, [
            'topic_group' => 'scrm', // topic组名
            'consume_group' => 'add_clue', // 消费组名
        ])->consumer();
    }
}
<?php

namespace Lwz\LaravelExtend\MQ\Commands;

use Illuminate\Console\Command;
use Lwz\LaravelExtend\MQ\Interfaces\MQReliableConsumerInterface;

class MQConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mq:consumer {topic_group} {consumer_group}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'mq消费者';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        app(MQReliableConsumerInterface::class, [
            'topic_group' => $this->argument('topic_group'), // topic组名
            'consume_group' => $this->argument('consumer_group'), // 消费组名
        ])->consumer();
    }
}

**目前只支持rocketmq（<span style="color:red;">基于阿里云的rocketmq封装</span>）**

### 可靠投递实现原理

![image-20211029104801989](images/image-20211029104801989.png)

### 安装

1. 下载组件包
   ```shell
   composer require lwz/laravel-mq
   # 安装迁移文件扩展包
   composer require zedisdog/laravel-schema-extend --dev
   ```
   
2. 发布配置文件

   > mq.php: 队列配置文件

   ```shell
   php artisan vendor:publish --provider="Lwz\LaravelExtend\MQ\MQServiceProvider"
   ```

   默认的日志驱动如下，如果需要配置，在配置文件 `logging.php` 的 `channels` 中对 `queuelog` 进行修改

   ```
   'queuelog' => [
       'driver' => 'daily',
       'path' => storage_path('logs/queue.log'),
       'level' => env('LOG_LEVEL', 'debug'),
       'days' => 50,
   ],
   ```

3. 创建基础表（**如果表已存在，跳过**）

   > mq_status_log：队列状态日志表
   >
   > mq_error_log：队列错误日志表

   ```shell
   php artisan migrate
   ```

4. 注册服务提供者 在 config/app.php 注册 ServiceProvider (Laravel 5.5 + 无需手动注册)
   ```php
   'providers' => [
        // ...
        Lwz\LaravelExtend\MQ\MQServiceProvider::class,
    ],
   ```
   
5. 队列日志驱动 `queuelog`，如果需要自定义在 `logging.php` 中新增 `queuelog` 驱动


### RocketMQ使用

> 目前只支持 RocketMQ

#### 1. 配置文件设置

> 配置文件名：`mq.php`

```php
return [
    'mq_type' => MQConst::TYPE_ROCKETMQ, // 队列类型

    /**
     * rocketmq 相关配置。队列关键参数：
     * instance_id => topic => message tag
     */
    'rocketmq' => [
        'http_endpoint' => env('ROCKETMQ_HTTP_ENDPOINT'),
        'access_key' => env('ROCKETMQ_ACCESS_KEY'),
        'secret_key' => env('ROCKETMQ_SECRET_KEY'),
        'topic_group' => [ // topic分组
//            'scrm' => [ // scrm实例
//                'instance_id' => '实例id',
//                'topic' => 'topic名称',
//            ]
        ],
        'consume_group' => [ // 消费者分组
//            'add_clue' => [ // 消费组名称
//                'msg_tag' => 'clue', // 消息标签
//                'group_id' => 'scrm_clue', // 分组id
//                'handle_class' => '', // 处理的消息的类名。必须继承 Lwz\LaravelExtend\MQ\Interfaces\ConsumerInterface 接口
//            ],
        ],
    ]
];
```

#### 2. 生产消息示例

##### 2.1 发送单条

````php
// 第一步：创建生产者对象
$mqObj = app(MQReliableProducerInterface::class,[
    'topic_group' => 'scrm', // topic组名
    'msg_tag' => 'clue', // 消息标签组名
    //            'delay_time' => '延迟时间戳（具体时间的时间戳，如：strotime(2022-10-10 10:32:43)，可以不传 或 传 null）',
    //            'msg_key' => '消息唯一标识（如果没传会默认生成一个唯一字符串），如：订单号',
]);

DB::transaction(function () use ($mqObj) {
    // todo 业务代码
    // xxxxxxxx
    // 第二步：调用 publishPrepare() 方法，记录消息状态
    $data = []; // 需要推送到队列的数据
    $mqObj->publishPrepare($data);
});

// 第三步：将消息推送到队列中
$mqObj->publishMessage();
````

##### 2.2 批量发送

```php
// 获取MQ对象
$mqObj = app(MQReliableProducerInterface::class, [
    'multi_data' => true, // 发送多条数据
]);
$mqObj->publishPrepare([
    [
        'topic_group' => 'group_test2', // topic 分组
        'msg_tag' => 'develop_test1', // 消息标签
        'payload' => ['dfg'], // 消息内容（数组）
        'delay_time' => null, // 延迟时间（具体的某个时间点，可以不传 或 传 null）
    ],
    [
        'topic_group' => 'group_test1', // topic 分组
        'msg_tag' => 'develop_test2', // 消息标签
        'payload' => ['ghj'], // 消息内容（数组）
        'delay_time' => null, // 延迟时间（具体的某个时间点，可以不传 或 传 null）
    ],
]);
$mqObj->publishMessage();
```

#### 3. 消费消息示例

> 注意：msg_tag 必须在 `mq.php` 配置文件中的`routes`指定消费类，否则消费失败

```shell
app(MQReliableConsumerInterface::class, [
    'topic_group' => 'scrm', // topic组名
    'consume_group' => 'add_clue', // 消费组名
])->consumer();
```

#### 4. 消息幂等性处理

msgKey：消息唯一标识（可用于做幂等性处理）

#### 5. 守护进程，监听失败消息重新投递

> 由于所有消息都记录在同一张表里，因此只需要启动一个 进程 即可，否则会产生多次投递的问题

```shell
php artisan mq:reproduce
```

#### 6. 消费进程
```shell
php artisan mq:consumer topic组名称 消费者名称
```

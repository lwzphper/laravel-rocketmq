### 使用步骤
1. 安装
   ```shell
   composer require lwz/laravel-mq
   ```
   
2. 发布配置文件

   > mq.php: 队列配置文件

   ```shell
   php artisan vendor:publish --provider="Lwz\LaravelExtend\MQ\MQServiceProvider"
   ```

3. 创建基础表（如果表已存在，跳过）

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

5. 监听失败队列


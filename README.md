### 使用步骤
1. 安装
   ```shell
   composer require lwz/laravel-mq
   ```
2. 注册服务提供者 在 config/app.php 注册 ServiceProvider(Laravel 5.5 + 无需手动注册)
   ```php
   'providers' => [
        // ...
        Lwz\LaravelExtend\Artisan\ArtisanServiceProvider::class,
    ],
   ```
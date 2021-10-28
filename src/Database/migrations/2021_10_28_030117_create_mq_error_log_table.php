<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Jialeo\LaravelSchemaExtend\Schema;

class CreateMqErrorLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mq_error_log', function (Blueprint $table) {
            $table->id();
            $table->string('mq_uuid', 30)->default('')->comment('mq唯一标识（用于更新或删除）');
            $table->string('mq_config', 1500)->default('')->comment('mq配置信息（为了兼容各mq）');
            $table->text('payload')->nullable()->comment('消息体');
            $table->text('error_msg')->nullable()->comment('异常消息');
            $table->timestamps();

            $table->index('mq_uuid', 'idx_mq_uuid');

            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment = 'mq错误日志';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mq_error_log');
    }
}

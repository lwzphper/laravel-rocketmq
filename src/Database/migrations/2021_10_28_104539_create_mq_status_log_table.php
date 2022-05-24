<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Jialeo\LaravelSchemaExtend\Schema;

class CreateMqStatusLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mq_status_log', function (Blueprint $table) {
            $table->id();
            $table->string('mq_uuid', 30)->default('')->comment('mq唯一标识（用于更新或删除）');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态。1：待发送；2：待消费（已发送到broker）；');
            $table->string('mq_config', 1500)->default('')->comment('mq配置信息（为了兼容各mq）');
            $table->unsignedTinyInteger('retry_num')->default(0)->comment('重试次数');
            $table->text('payload')->nullable()->comment('消息体');
            $table->timestamp('created_at')->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新时间');

            $table->unique('mq_uuid', 'uk_mq_uuid');
            $table->index('updated_at', 'idx_updated_at');

            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment = '队列状态日志';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mq_status_log');
    }
}

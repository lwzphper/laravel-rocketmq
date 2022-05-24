<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/12/1 22:49,
 * @LastEditTime: 2021/12/1 22:49
 */
declare(strict_types=1);

use Lwz\LaravelExtend\MQ\Interfaces\MQReliableProducerInterface;

class producer
{
    public function handle()
    {
        $mqObj = app(MQReliableProducerInterface::class,[
            'topic_group' => 'group_test2', // topic组名
            'msg_tag' => 'develop_test1', // 消息标签组名
//            'delay_time' => '延迟时间（具体的某个时间点，可以不传 或 传 null）',
//            'msg_key' => '消息唯一标识（如果没传会默认生成一个唯一字符串），如：订单号',
        ]);

        $mqObj->publishPrepare(['test_id']);

        // 第三步：将消息推送到队列中
        $ret = $mqObj->publishMessage();
        dd($ret);
    }

    /**
     * 批量发送数据
     * @author lwz
     */
    public function multiData()
    {
        // 获取MQ对象
        $mqObj = app(MQReliableProducerInterface::class, [
            'multi_data' => true,
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
    }
}

<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2022/02/21 11:13,
 * @LastEditTime: 2022/02/21 11:13
 */

namespace Lwz\LaravelExtend\MQ\Library;


class MQHelper
{
    /**
     * 设置 rocketMQ 的消息标签后缀
     * @param string|null $msgTag 消息标签
     * @return string
     * @author lwz
     */
    public static function setRocketMQMsgTagExt(?string $msgTag): string
    {
        if (is_null($msgTag)) {
            return '';
        }

        if ($ext = config('mq.rocketmq.msg_tag_ext')) {
            $msgTagDelimiter = '||'; // 消息标签分隔符，考虑消费监听多个消息标签的情况
            return implode($msgTagDelimiter, array_map(function ($tag) use ($ext) {
                return $tag . '_' . $ext;
            }, explode($msgTagDelimiter, $msgTag)));
        }
        return $msgTag;
    }
}
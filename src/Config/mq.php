<?php
/**
 * 消息队列相关配置
 */
return [
    'reproduce_max_num' => 3, // 最大重新投递次数
    'reproduce_time' => 600, // 重新投递的时间（相当于更新时间）
];

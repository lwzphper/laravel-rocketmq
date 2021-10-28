<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:51,
 * @LastEditTime: 2021/10/27 18:51
 */

namespace Lwz\LaravelExtend\MQ\Library\RocketMQ;


use MQ\Config;
use MQ\MQClient;

/**
 * Class RocketMQClient
 * @package Lwz\LaravelExtend\MQ\Library\RocketMQ
 * @author lwz
 * MQ 客户端（单例模式）
 */
class RocketMQClient
{
    /**
     * 节点
     * @var string
     */
    private string $_endPoint;

    /**
     * accessId
     * @var string
     */
    private string $_accessId;

    /**
     * accessKey
     * @var string
     */
    private string $_accessKey;

    /**
     * MQ 客户端
     * @var MQClient
     */
    protected MQClient $client;

    /**
     * MQ对象
     * @var self|null
     */
    private static ?RocketMQClient $_instance = null;

    private function __construct()
    {
        $this->_endPoint = config('mq.rocketmq.http_endpoint');
        $this->_accessId = config('mq.rocketmq.access_key');
        $this->_accessKey = config('mq.rocketmq.secret_key');

        // 设置客户端
        $this->_setClient();
    }

    /**
     * 禁止克隆操作
     * @author lwz
     */
    private function __clone()
    {
    }

    /**
     * 获取实例
     * @return static
     * @author lwz
     */
    public static function getInstance(): self
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 设置客户端（防止 http 请求中断）
     * @author lwz
     */
    private function _setClient(): MQClient
    {
        $mqConfig = new Config();
//        $mqConfig->setRequestTimeout(3); // 设置请求超时时间（如果配置和这个参数，获取队列响应时，会有 Call to undefined method GuzzleHttp\Exception\ConnectException::hasResponse() 异常）

        return $this->client = new MQClient(
            $this->_endPoint,
            $this->_accessId,
            $this->_accessKey,
            null,
            $mqConfig
        );
    }

    /**
     * 获取客户端
     * @return MQClient
     * @author lwz
     */
    public function getClient(): MQClient
    {
        return $this->client;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: ryanchan
 * Date: 30/12/2017
 * Time: 6:42 PM
 */

namespace Yansongda\Pay\Gateways;


use Yansongda\Pay\Contracts\GatewayApplicationInterface;
use Yansongda\Pay\Gateways\Wechat\Support;
use Yansongda\Supports\Config;

class WechatHK extends Wechat implements GatewayApplicationInterface
{
    /**
     * WechatHK constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);

        $this->gateway = Support::baseUri('hk_wallet');
    }
}
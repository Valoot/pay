<?php
/**
 * Created by PhpStorm.
 * User: dominwong
 * Date: 11/7/2019
 * Time: 11:34 AM
 */

namespace Yansongda\Pay\Gateways\AlipayHK;


use Yansongda\Pay\Contracts\GatewayInterface;
use Yansongda\Pay\Log;
use Yansongda\Supports\Collection;
use Yansongda\Supports\Config;

class AppGateway implements GatewayInterface
{
    protected $config;

    /**
     * Bootstrap.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function pay($endpoint, array $payload)
    {
        $payload['service'] = $this->getMethod();
        $payload['seller_id'] = $this->config->get('partner_id');
        $payload['sign_type'] = "RSA";
        $payload['it_b_pay'] = "10m";

        unset($payload['return_url'], $payload['timestamp']);

        ksort($payload);
        $payload['sign'] = urlencode(Support::generateRSASign(array_except($payload, ['sign_type', 'sign']), $this->config->get('rsa_key'),'"'));

        Log::debug('Paying A App Order:', [$endpoint, $payload]);

        return new Collection($payload);
    }

    protected function getMethod()
    {
        return 'mobile.securitypay.pay';
    }
}

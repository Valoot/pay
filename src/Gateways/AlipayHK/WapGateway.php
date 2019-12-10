<?php
/**
 * Created by PhpStorm.
 * User: hoyinwong
 * Date: 2019-12-10
 * Time: 15:28
 */

namespace Yansongda\Pay\Gateways\AlipayHK;


use Yansongda\Pay\Contracts\GatewayInterface;
use Yansongda\Pay\Log;
use Yansongda\Supports\Collection;
use Yansongda\Supports\Config;

class WapGateway implements GatewayInterface
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
        $payload['sign_type'] = "RSA";

        unset($payload['return_url'], $payload['timestamp'], $payload['notify_url'], $payload['payment_inst'], $payload['secondary_merchant_id'], $payload['trade_information']);
        ksort($payload);
        $payload['sign'] = Support::generateRSASign(array_except($payload, ['sign_type', 'sign']), $this->config->get('rsa_key'));

        Log::debug('Paying A App Order:', [$endpoint, $payload]);

        return new Collection($payload);
    }

    protected function getMethod()
    {
        return 'create_forex_trade_wap';
    }
}

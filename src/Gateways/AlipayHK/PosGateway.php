<?php
/**
 * Created by PhpStorm.
 * User: ryanchan
 * Date: 5/1/2018
 * Time: 5:38 PM
 */

namespace Yansongda\Pay\Gateways\AlipayHK;


use Yansongda\Pay\Log;
use Yansongda\Supports\Collection;

class PosGateway extends \Yansongda\Pay\Gateways\Alipay\PosGateway
{
    public function pay($endpoint, array $payload): Collection
    {
        $payload['service'] = $this->getMethod();
        $payload['alipay_seller_id'] = $this->config->get('partner_id');
        $payload['trans_create_time'] = date('YmdHis');

        unset($payload['notify_url'], $payload['return_url'], $payload['timestamp']);

        $payload['sign'] = Support::generateSign(array_except($payload, ['sign_type', 'sign']), $this->config->get('md5_key'));

        Log::debug('Paying A Pos Order:', [$endpoint, $payload]);

        ksort($payload);

        return Support::requestApi($payload, $this->config->get('md5_key'));
    }

    protected function getMethod()
    {
        return 'alipay.acquire.overseas.spot.pay';
    }
}
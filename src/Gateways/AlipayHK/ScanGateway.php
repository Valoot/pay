<?php
/**
 * Created by PhpStorm.
 * User: ryanchan
 * Date: 2/1/2018
 * Time: 5:46 PM
 */

namespace Yansongda\Pay\Gateways\AlipayHK;


use Yansongda\Pay\Log;
use Yansongda\Supports\Collection;

class ScanGateway extends \Yansongda\Pay\Gateways\Alipay\ScanGateway
{
    public function pay($endpoint, array $payload): Collection
    {
        $payload['service'] = $this->getMethod();

        unset($payload['return_url']);

        $payload['sign'] = Support::generateSign(array_except($payload, ['sign_type', 'sign']), $this->config->get('md5_key'));

        Log::debug('Paying A Scan Order:', [$endpoint, $payload]);

        ksort($payload);

        return Support::requestApi($payload, $this->config->get('md5_key'));
    }

    protected function getMethod(): string
    {
        return 'alipay.acquire.precreate';
    }
}
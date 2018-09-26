<?php

namespace Yansongda\Pay\Gateways\Wechat;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Yansongda\Supports\Str;
use Yansongda\Supports\Collection;
use Yansongda\Pay\Log;

class AppGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param string $endpoint
     * @param array  $payload
     *
     * @return Collection
     */
    public function pay($endpoint, array $payload): Collection
    {
        $payload['appid'] = $this->config->get('app_id');
	    $payload['sub_appid'] = $this->config->get('sub_appid');
        $payload['trade_type'] = $this->getTradeType();

        $payRequest = [
            'appid'     => $payload['sub_appid'],
            'partnerid' => $payload['sub_mch_id'],
            'prepayid'  => $this->preOrder('pay/unifiedorder', $payload)->prepay_id,
            'timestamp' => strval(time()),
            'noncestr'  => Str::random(),
            'package'   => 'Sign=WXPay',
        ];
        $payRequest['sign'] = Support::generateSign($payRequest, $this->config->get('key'));

        Log::debug('Paying An App Order:', [$endpoint, $payRequest]);

        return new Collection($payRequest);
    }

    /**
     * Get trade type config.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @return string
     */
    protected function getTradeType(): string
    {
        return 'APP';
    }
}

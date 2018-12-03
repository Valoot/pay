<?php

namespace Yansongda\Pay\Gateways\Wechat;

use Yansongda\Pay\Log;
use Yansongda\Supports\Collection;
use Yansongda\Supports\Str;

class MiniappGateway extends MpGateway
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
	    $payload['trade_type'] = $this->getTradeType();
	    $payload['appid'] = $this->config->get('miniapp_id');

	    $payRequest = [
		    'appId'     => $payload['sub_appid'],
		    'timeStamp' => strval(time()),
		    'nonceStr'  => Str::random(),
		    'package'   => 'prepay_id='.$this->preOrder('pay/unifiedorder', $payload)->prepay_id,
		    'signType'  => 'MD5',
	    ];
	    $payRequest['paySign'] = Support::generateSign($payRequest, $this->config->get('key'));

	    Log::debug('Paying A JSAPI Order:', [$endpoint, $payRequest]);

	    return new Collection($payRequest);
    }
}

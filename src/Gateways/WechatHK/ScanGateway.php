<?php
/**
 * Created by PhpStorm.
 * User: ryanchan
 * Date: 26/7/2018
 * Time: 11:13 PM
 */

namespace Yansongda\Pay\Gateways\WechatHK;


use Symfony\Component\HttpFoundation\Request;
use Yansongda\Pay\Gateways\Wechat\Gateway;
use Yansongda\Supports\Collection;

class ScanGateway extends Gateway
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
	public function pay($endpoint, array $payload)
	{
		$payload['spbill_create_ip'] = Request::createFromGlobals()->server->get('SERVER_ADDR');
		$payload['trade_type'] = $this->getTradeType();

		return $this->preOrder('ia_mch_unified_orderreg.cgi', $payload);
	}

	/**
	 * Get trade type config.
	 *
	 * @author yansongda <me@yansongda.cn>
	 *
	 * @return string
	 */
	protected function getTradeType()
	{
		return 'NATIVE';
	}
}
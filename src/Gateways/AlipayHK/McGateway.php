<?php
/**
 * Created by PhpStorm.
 * User: ryanchan
 * Date: 15/11/2018
 * Time: 10:59 AM
 */

namespace Yansongda\Pay\Gateways\AlipayHK;


use Yansongda\Pay\Contracts\GatewayInterface;
use Yansongda\Supports\Collection;
use Yansongda\Supports\Config;

class McGateway implements GatewayInterface
{
	/**
	 * @var \Yansongda\Supports\Config
	 */
	protected $config;

	/**
	 * McGateway constructor.
	 *
	 * @param \Yansongda\Supports\Config $config
	 */
	public function __construct(Config $config)
	{
		$this->config = $config;
	}

	/**
	 * Pay an order.
	 *
	 * @author yansongda <me@yansongda.cn>
	 *
	 * @param string $endpoint
	 * @param array  $payload
	 *
	 * @return Yansongda\Supports\Collection|Symfony\Component\HttpFoundation\Response
	 */
	public function pay($endpoint, array $payload)
	{
		$payload['service'] = $this->getMethod();
		$payload['biz_type'] = $this->getProductCode();
		$payload['biz_data'] = json_encode(json_decode($payload['biz_data'], true));
		$payload['sign'] = Support::generateSign(array_except($payload, ['sign_type', 'sign']), $this->config->get('md5_key'));

		Log::debug('Paying A Scan Order:', [$endpoint, $payload]);

		ksort($payload);

		return Support::requestApi($payload, $this->config->get('md5_key'));
	}

	protected function getMethod()
	{
		return 'alipay.commerce.qrcode.create';
	}

	protected function getProductCode()
	{
		return 'OVERSEASHOPQRCODE';
	}
}
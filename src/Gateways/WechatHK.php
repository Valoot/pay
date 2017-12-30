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
use Yansongda\Supports\Collection;
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

    public function find($order): Collection
    {
        if (is_array($order)) {
            $this->payload = array_merge($this->payload, $order);
        } else {
            $this->payload['out_trade_no'] = $order;
        }

        unset($this->payload['notify_url'], $this->payload['trade_type']);

        $this->payload['sign'] = Support::generateSign($this->payload, $this->config->get('key'));

        return Support::requestApi('ia_mch_qr_order_query.cgi', $this->payload, $this->config->get('key'));
    }

    public function close($order)
    {
        if (is_array($order)) {
            $this->payload = array_merge($this->payload, $order);
        } else {
            $this->payload['out_trade_no'] = $order;
        }

        unset($this->payload['notify_url'], $this->payload['trade_type']);

        $this->payload['sign'] = Support::generateSign($this->payload, $this->config->get('key'));

        return Support::requestApi('ia_mch_qr_order_revoke.cgi', $this->payload, $this->config->get('key'));
    }

    public function refund($order): Collection
    {
        if (isset($order['miniapp'])) {
            $this->payload['appid'] = $this->config->get('miniapp_id');
            unset($order['miniapp']);
        }

        $this->payload = array_merge($this->payload, $order);

        unset($this->payload['notify_url'], $this->payload['trade_type']);

        $this->payload['sign'] = Support::generateSign($this->payload, $this->config->get('key'));

        return Support::requestApi(
            'api/ia_mch_qr_order_refund.cgi',
            $this->payload,
            $this->config->get('key'),
            $this->config->get('cert_client'),
            $this->config->get('cert_key')
        );
    }


}
<?php
/**
 * Created by PhpStorm.
 * User: ryanchan
 * Date: 2/1/2018
 * Time: 4:43 PM
 */

namespace Yansongda\Pay\Gateways;


use Yansongda\Pay\Contracts\GatewayApplicationInterface;
use Yansongda\Pay\Exceptions\GatewayException;
use Yansongda\Pay\Gateways\AlipayHK\Support;
use Yansongda\Supports\Collection;
use Yansongda\Supports\Config;
use Yansongda\Supports\Str;

class AlipayHK extends Alipay implements GatewayApplicationInterface
{
    public function __construct(Config $config)
    {
        parent::__construct($config);

        // use hk_wallet
        $this->gateway = Support::baseUri($this->config->get('mode', 'hk_wallet'));

        // unset appid for alipay hk wallet
        unset(
            $this->payload['app_id'],
            $this->payload['charset'],
            $this->payload['format'],
            $this->payload['version'],
            $this->payload['biz_content'],
            $this->payload['method']
        );

        // setup hk wallet required fields
        $this->payload = array_merge($this->payload, [
            'service' => '',
            'partner' => $this->config->get('partner_id', null),
            '_input_charset' => 'UTF-8',
            // use MD5 sign type for alipay hk wallet
            'sign_type' => 'MD5',
            'timestamp' => time(),
        ]);
    }

    public function pay($gateway, $params = [])
    {
        $this->payload = array_merge($this->payload, $params);

        $gateway = get_class($this) . '\\' . Str::studly($gateway) . 'Gateway';

        if (class_exists($gateway)) {
            return $this->makePay($gateway);
        }

        throw new GatewayException("Pay Gateway [{$gateway}] not exists", 1);
    }

    public function find($order): Collection
    {
        $this->payload['service'] = 'alipay.acquire.overseas.query';

        if (is_array($order)) {
            $this->payload = array_merge($this->payload, $order);
        } else {
            $this->payload['partner_trans_id'] = $order;
        }

        unset(
            $this->payload['return_url'],
            $this->payload['notify_url'],
            $this->payload['timestamp']
        );

        $key = $this->config->get('rsa_key') ?: $this->config->get('md5_key');

        if ($this->config->get('rsa_key') != null) {
            $this->payload['sign_type'] = "RSA";
            $this->payload['service'] = "single_trade_query";
            $this->payload['out_trade_no'] = $this->payload['partner_trans_id'];
            unset($this->payload['partner_trans_id']);
            $this->payload['sign'] = Support::generateRSASign(array_except($this->payload, ['sign_type', 'sign']), $key);
            return Support::requestApi($this->payload, $key);
        }

        $this->payload['sign'] = Support::generateSign(array_except($this->payload, ['sign_type', 'sign']), $key);

        \Log::debug('Find An Order:', [$this->gateway, $this->payload]);

        return Support::requestApi($this->payload, $key);
    }

    /**
     * Cancel an order.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param string|array $order
     *
     * @return Collection
     */
    public function cancel($order): Collection
    {
        $this->payload['service'] = 'alipay.acquire.cancel';

        if (is_array($order)) {
            $this->payload = array_merge($this->payload, $order);
        } else {
            $this->payload['out_trade_no'] = $order;
        }

        unset(
            $this->payload['return_url'],
            $this->payload['notify_url'],
            $this->payload['timestamp']
        );

        $this->payload['sign'] = Support::generateSign(array_except($this->payload, ['sign_type', 'sign']), $this->config->get('md5_key'));

        \Log::debug('Cancel An Order:', [$this->gateway, $this->payload]);

        return Support::requestApi($this->payload, $this->config->get('md5_key'));
    }

    public function refund($order): Collection
    {
        $this->payload['service'] = 'alipay.acquire.overseas.spot.refund';
        $this->payload = array_merge($this->payload, $order);

        unset($this->payload['return_url']);

        $key = $this->config->get('rsa_key') ?: $this->config->get('md5_key');

        if ($this->config->get('rsa_key') != null) {
            $this->payload['sign_type'] = 'RSA';
            $this->payload['service'] = 'forex_refund';
            $this->payload['out_trade_no'] = $this->payload['partner_trans_id'];
            $this->payload['currency'] = $order['currency'];
            $this->payload['reason'] = 'test';
            $this->payload['out_return_no'] = $this->payload['partner_refund_id'];
            $this->payload['return_amount'] = $this->payload['refund_amount'];
            $this->payload['notify_url'] = 'http://fb3077b2.ngrok.io/v1/transactions/callback';
            unset($this->payload['partner_trans_id'], $this->payload['timestamp'], $this->payload['partner_refund_id'], $this->payload['refund_amount']);
            $this->payload['sign'] = Support::generateRSASign(array_except($this->payload, ['sign_type', 'sign']), $key);
            return Support::requestApi($this->payload, $key);
        }

        $this->payload['sign'] = Support::generateSign(array_except($this->payload, ['sign_type', 'sign']), $key);

        \Log::debug('Find An Order:', [$this->gateway, $this->payload]);

        return Support::requestApi($this->payload, $key);
    }


}

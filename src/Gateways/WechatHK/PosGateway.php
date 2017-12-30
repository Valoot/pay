<?php
/**
 * Created by PhpStorm.
 * User: ryanchan
 * Date: 30/12/2017
 * Time: 6:46 PM
 */

namespace Yansongda\Pay\Gateways\WechatHK;


use Yansongda\Pay\Gateways\Wechat\Gateway;
use Yansongda\Supports\Collection;

class PosGateway extends Gateway
{
    /**
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection
     */
    public function pay($endpoint, array $payload): Collection
    {
        unset($payload['trade_type'], $payload['notify_url']);

        return $this->preOrder('ia_mch_qr_auth.cgi', $payload);
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
        return '';
    }
}
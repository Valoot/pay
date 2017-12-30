<?php
/**
 * Created by PhpStorm.
 * User: ryanchan
 * Date: 30/12/2017
 * Time: 6:46 PM
 */

namespace Yansongda\Pay\Gateways\WechatHK;


use Yansongda\Supports\Collection;

class PosGateway extends \Yansongda\Pay\Gateways\Wechat\PosGateway
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

}
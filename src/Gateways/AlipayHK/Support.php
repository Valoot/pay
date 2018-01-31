<?php
/**
 * Created by PhpStorm.
 * User: ryanchan
 * Date: 3/1/2018
 * Time: 4:35 PM
 */

namespace Yansongda\Pay\Gateways\AlipayHK;


use Yansongda\Pay\Exceptions\GatewayException;
use Yansongda\Pay\Exceptions\InvalidSignException;
use Yansongda\Pay\Log;
use Yansongda\Supports\Collection;

class Support extends \Yansongda\Pay\Gateways\Alipay\Support
{
    public static function generateSign($parmas, $privateKey = null): string
    {
        ksort($parmas);

        return md5(urldecode(http_build_query($parmas)) . $privateKey);
    }

    public static function requestApi($data, $publicKey): Collection
    {
        Log::debug('Request To Alipay Api', [self::baseUri(), $data]);

        $data = self::getInstance()->post('', $data);

        if (!self::verifySign($data['response']['alipay'], $publicKey, true, $data['sign'])) {
            Log::warning('Alipay Sign Verify FAILED', $data);

            throw new InvalidSignException('Alipay Sign Verify FAILED', 3, $data);
        }

        if (isset($data['response']['alipay']['result_code']) && $data['response']['alipay']['result_code'] === 'SUCCESS') {
            return new Collection($data['response']['alipay']);
        }

        throw new GatewayException(
            'Get Alipay API error, please check raw data for further information.',
            $data,
            json_encode($data)
        );
    }

    public static function verifySign($data, $publicKey = null, $sync = false, $sign = null): bool
    {
        $toBeVerified  = md5(
            mb_convert_encoding(
                urldecode(http_build_query($data)) . $publicKey,
                'gb2312',
                'utf-8'
            )
        );

        return $sign === $toBeVerified;
    }
}
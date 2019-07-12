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

        if (array_has($data, 'is_success') && $data['is_success'] === 'F') {
            throw new InvalidSignException('Alipay Error', 3, $data);
        }

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

    /**
     * Generate sign.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @return string
     */
    public static function generateRSASign($parmas, $privateKey = null): string
    {
        if (is_null($privateKey)) {
            throw new InvalidConfigException('Missing Alipay Config -- [private_key]', 1);
        }

        if (Str::endsWith($privateKey, '.pem')) {
            $privateKey = openssl_pkey_get_private($privateKey);
        } else {
            $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n".
                wordwrap($privateKey, 64, "\n", true).
                "\n-----END RSA PRIVATE KEY-----";
        }

        openssl_sign(self::getSignContent($parmas), $sign, $privateKey, OPENSSL_ALGO_SHA1);

        return base64_encode($sign);
    }

    /**
     * Verfiy sign.
     *
     * @author yansongda <me@yansonga.cn>
     *
     * @param array       $data
     * @param string      $publicKey
     * @param bool        $sync
     * @param string|null $sign
     *
     * @return bool
     */
    public static function verifyRSASign($data, $publicKey = null, $sync = false, $sign = null): bool
    {
        if (is_null($publicKey)) {
            throw new InvalidConfigException('Missing Alipay Config -- [ali_public_key]', 2);
        }

        if (Str::endsWith($publicKey, '.pem')) {
            $publicKey = openssl_pkey_get_public($publicKey);
        } else {
            $publicKey = "-----BEGIN PUBLIC KEY-----\n".
                wordwrap($publicKey, 64, "\n", true).
                "\n-----END PUBLIC KEY-----";
        }

        $sign = $sign ?? $data['sign'];

        $toVerify = $sync ? mb_convert_encoding(json_encode($data, JSON_UNESCAPED_UNICODE), 'gb2312', 'utf-8') : self::getSignContent($data, true);

        return openssl_verify($toVerify, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA1) === 1;
    }
}

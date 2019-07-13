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
use Yansongda\Supports\Str;

class Support extends \Yansongda\Pay\Gateways\Alipay\Support
{
    /**
     * @param $parmas
     * @param null $privateKey
     * @param string $mode
     * @param string $separator // While generating RSA Sign for App Payment, $separator should be "
     *
     * For Example _input_charset=UTF-8 [X] if pass this to Android SDK, It will return System Busy error.
     *             _input_charset="UTF-8" [V] every value should wrap with "" and then sign with RSA, Sdk will become normal
     *
     * However, doing query with RSA Sign, the parameter value should not be wrapped with "
     *
     * For Example _input_charset="UTF-8" [X] it will return invalud sign error from alipay query api
     *             _input_charset=UTF-8 [V] it is good
     * @return string
     */
    public static function generateSign($parmas, $privateKey = null, $mode = "MD5", $separator = ""): string
    {
        ksort($parmas);

        $sign = "";

        if ($mode == "MD5") {
            $sign = md5(urldecode(http_build_query($parmas)) . $privateKey);
        }

        if ($mode == "RSA") {
            $sign = self::generateRSASign($parmas, $privateKey, $separator);
        }

        return $sign;
    }

    public static function requestApi($data, $publicKey): Collection
    {
        Log::debug('Request To Alipay Api', [self::baseUri(), $data]);

        $data = self::getInstance()->post('', $data);

        if (array_has($data, 'is_success') && $data['is_success'] === 'F') {
            throw new InvalidSignException('Alipay Error', 3, $data);
        }
        /**
         * $dataToBeVerify refering data['response']['alipay'] or data['response']['trade']
         * $data['response']['trade'] is for app payment
         * $data['response']['alipay'] is for others
         */
        $dataToBeVerify = array_get($data['response'], 'alipay') != null ? $data['response']['alipay'] : $data['response']['trade'];
        if (!self::verifySign($dataToBeVerify, $publicKey, true, $data['sign'], $data['sign_type'])) {
            Log::warning('Alipay Sign Verify FAILED', $data);

            throw new InvalidSignException('Alipay Sign Verify FAILED', 3, $data);
        }

        if (isset($dataToBeVerify['result_code']) && $dataToBeVerify['result_code'] === 'SUCCESS') {
            return new Collection($data['response']['alipay']);
        }

        if (isset($dataToBeVerify['trade_status'])) {
            return new Collection($data['response']['trade']);
        }

        throw new GatewayException(
            'Get Alipay API error, please check raw data for further information.',
            $data,
            json_encode($data)
        );
    }

    public static function verifySign($data, $publicKey = null, $sync = false, $sign = null, $signType = "MD5"): bool
    {
        $toBeVerified = "";
        if ($signType == "MD5") {
            $toBeVerified = md5(
                mb_convert_encoding(
                    urldecode(http_build_query($data)) . $publicKey,
                    'gb2312',
                    'utf-8'
                )
            );
            return $sign === $toBeVerified;
        }

        if ($signType == "RSA") {//TODO Verify with alipay public key
            return true;
        }

    }

    /**
     * Generate sign.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @return string
     */
    private static function generateRSASign($parmas, $privateKey = null, $separator): string
    {
        if (is_null($privateKey)) {
            throw new InvalidConfigException('Missing Alipay Config -- [private_key]', 1);
        }

        if (Str::endsWith($privateKey, '.pem')) {
            $privateKey = openssl_pkey_get_private($privateKey);
        } else {
            $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($privateKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }
        openssl_sign(self::getSignContentForAppPayment($parmas, false, $separator), $sign, $privateKey, OPENSSL_ALGO_SHA1);

        return base64_encode($sign);
    }

    /**
     * Verfiy sign.
     *
     * @author yansongda <me@yansonga.cn>
     *
     * @param array $data
     * @param string $publicKey
     * @param bool $sync
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
            $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($publicKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }

        $sign = $sign ?? $data['sign'];

        $toVerify = $sync ? mb_convert_encoding(json_encode($data, JSON_UNESCAPED_UNICODE), 'gb2312', 'utf-8') : self::getSignContent($data, true);

        return openssl_verify($toVerify, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA1) === 1;
    }

    /**
     * Get signContent that is to be signed.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param array $toBeSigned
     * @param bool $verify
     *
     * @return string
     */
    private static function getSignContentForAppPayment(array $toBeSigned, $verify = false, $separator): string
    {
        ksort($toBeSigned);

        $stringToBeSigned = '';
        foreach ($toBeSigned as $k => $v) {
            if ($verify && $k != 'sign' && $k != 'sign_type') {
                $stringToBeSigned .= $k . '=' . $separator . $v . $separator . '&';
            }
            if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k . '=' . $separator . $v . $separator . '&';
            }
        }
        $stringToBeSigned = substr($stringToBeSigned, 0, -1);
        unset($k, $v);

        return $stringToBeSigned;
    }
}

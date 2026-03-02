<?php

namespace Meridaura\PaymentManager\Support;

use Meridaura\PaymentManager\Exceptions\PaymentGatewayException;

class RsaSigner
{
    public static function sign(string $data, string $privateKey): string
    {
        $signature = '';

        $isSigned = openssl_sign($data, $signature, $privateKey);

        if (!$isSigned) {
            throw new PaymentGatewayException(
                'Failed to generate RSA signature: ' . openssl_error_string()
            );
        }

        return base64_encode($signature);
    }

    public static function verify(string $data, string $signature, string $publicKey): bool
    {
        $decodedSignature = base64_decode($signature);

        $result = openssl_verify($data, $decodedSignature, $publicKey);

        return $result === 1;
    }
}
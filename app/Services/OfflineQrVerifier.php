<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class OfflineQrVerifier
{
    public static function verify(array $payload): bool
    {
        if (!isset($payload['kid'], $payload['signature'])) {
            return false;
        }

        $kid = $payload['kid'];
        $signature = base64_decode($payload['signature']);

        unset($payload['signature']);

        $publicKeyPath = storage_path("keys/{$kid}.pub");

        if (!File::exists($publicKeyPath)) {
            return false;
        }

        ksort($payload);
        $data = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $publicKey = File::get($publicKeyPath);

        return sodium_crypto_sign_verify_detached(
            $signature,
            $data,
            $publicKey
        );
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class OfflineQrSigner
{
    public static function sign(array $payload): array
    {
        $kid = config('offline_qr.active_kid');
        $privateKeyPath = storage_path("keys/{$kid}");

        if (!File::exists($privateKeyPath)) {
            throw new \RuntimeException('Signing key not found');
        }

        $payload['kid'] = $kid;

        // Canonicalize (exclude signature)
        ksort($payload);
        $data = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $privateKey = File::get($privateKeyPath);

        $signature = sodium_crypto_sign_detached(
    $data,
    $privateKey
);

        $payload['signature'] = base64_encode($signature);

        return $payload;
    }
}

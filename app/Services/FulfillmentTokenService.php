<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FulfillmentTokenService
{
    public static function generate(int $saleId, int $warehouseId, int $expiresInMinutes = 60): string
    {
        $items = DB::table('sale_items')
            ->where('sale_id', $saleId)
            ->orderBy('product_id', 'asc')
            ->get(['product_id', 'quantity']);

        if ($items->isEmpty()) {
            throw new \Exception('Cannot generate token for empty sale');
        }

        // FORCE CONSISTENT TYPES AND FORMAT
        $dataToHash = $items->map(fn ($i) => [
            'product_id' => (int) $i->product_id,
            'quantity'   => (string) number_format((float) $i->quantity, 2, '.', ''),
        ])->values()->all();

        // Use flags to prevent escaping slashes or unicode differences
        $itemsHash = hash('sha256', json_encode($dataToHash, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $plainToken = Str::random(48);
        $tokenHash = hash('sha256', $plainToken);

        DB::table('fulfillment_tokens')->insert([
            'sale_id'      => $saleId,
            'warehouse_id' => $warehouseId,
            'token_hash'   => $tokenHash,
            'items_hash'   => $itemsHash,
            'used'         => false,
            'expires_at'   => now()->addMinutes($expiresInMinutes),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $plainToken;
    }
}
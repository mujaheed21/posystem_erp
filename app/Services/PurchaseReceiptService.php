<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class PurchaseReceiptService
{
    public function receive(
        Purchase $purchase,
        Warehouse $warehouse,
        User $agent,
        array $items
    ): PurchaseReceipt {
        // HARD RBAC ENFORCEMENT
        if (!$agent->can('purchase.receive')) {
    throw new \Illuminate\Auth\Access\AuthorizationException(
        'Only warehouse verification agents may receive purchased goods.'
    );
}


        return DB::transaction(function () use ($purchase, $warehouse, $agent, $items) {

            $receipt = PurchaseReceipt::create([
                'purchase_id' => $purchase->id,
                'warehouse_id' => $warehouse->id,
                'verified_by' => $agent->id,
                'received_at' => now(),
                'status' => $this->resolveStatus($items),
            ]);

            foreach ($items as $item) {
                PurchaseReceiptItem::create([
                    'purchase_receipt_id' => $receipt->id,
                    'product_id' => $item['product_id'],
                    'qty_received' => $item['received'],
                    'qty_rejected' => $item['rejected'] ?? 0,
                    'rejection_reason' => $item['reason'] ?? null,
                ]);

                if ($item['received'] > 0) {
                    app(StockService::class)->increase(
                        warehouse: $warehouse,
                        productId: $item['product_id'],
                        quantity: $item['received'],
                        source: 'purchase_receipt',
                        sourceId: $receipt->id
                    );
                }
            }

            app(AuditService::class)->log(
                actor: $agent,
                action: 'purchase.received',
                subject: $receipt
            );

            return $receipt;
        });
    }

    private function resolveStatus(array $items): string
    {
        $received = collect($items)->sum('received');
        $rejected = collect($items)->sum('rejected');

        return match (true) {
            $received > 0 && $rejected > 0 => 'partial',
            $received > 0 => 'received',
            default => 'rejected',
        };
    }
}

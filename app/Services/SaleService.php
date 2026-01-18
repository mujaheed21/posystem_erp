<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StockService;
use App\Services\AuditService;
use App\Services\LedgerService; 
use App\Models\Sale; 
use App\Models\Warehouse;

class SaleService
{
    protected $ledgerService;

    // Inject LedgerService via Constructor
    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Create a sale and reserve stock atomically.
     */
    public function create(array $saleData, array $items)
    {
        return DB::transaction(function () use ($saleData, $items) {

            $user = Auth::user();

            // 1. Create sale header
            $saleId = DB::table('sales')->insertGetId([
                'business_id'          => $saleData['business_id'],
                'business_location_id' => $saleData['business_location_id'],
                'warehouse_id'         => $saleData['warehouse_id'],
                'sale_number'          => $saleData['sale_number'],
                'subtotal'             => $saleData['subtotal'],
                'discount'             => $saleData['discount'] ?? 0,
                'tax'                  => $saleData['tax'] ?? 0,
                'total'                => $saleData['total'],
                'status'               => 'completed', 
                'created_by'           => $saleData['created_by'] ?? $user->id,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // 2. Create sale items
            $reservationItems = [];
            foreach ($items as $item) {
                DB::table('sale_items')->insert([
                    'sale_id'     => $saleId,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $reservationItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ];
            }

            // 3. Reserve stock (INTENT ONLY)
            app(StockService::class)->reserve(
                $saleData['warehouse_id'],
                $reservationItems
            );

            // 4. Post to General Ledger
            $sale = Sale::find($saleId);
            $this->recordFinancialEntry($sale);

            // 5. Audit sale creation
            AuditService::log(
                'sale_created',
                'sales',
                'sales',
                $saleId,
                [
                    'warehouse_id' => $saleData['warehouse_id'],
                    'total'        => $saleData['total'],
                ]
            );

            return $saleId;
        });
    }

    /**
     * Process a Sales Return
     * Reverses financial impact and restores stock.
     */
    public function processReturn(int $saleId, array $returnItems)
    {
        return DB::transaction(function () use ($saleId, $returnItems) {
            $sale = Sale::findOrFail($saleId);
            $user = Auth::user();

            foreach ($returnItems as $item) {
                // 1. Restore stock to warehouse
                $warehouse = Warehouse::find($sale->warehouse_id);
                app(StockService::class)->increase(
                    $warehouse,
                    $item['product_id'],
                    $item['quantity'],
                    'sale_return',
                    $saleId,
                    'sales',
                    $user->id
                );

                // 2. Financial Reversal Logic
                $totalReturnValue = $item['quantity'] * $item['unit_price'];

                $entries = [
                    // DEBIT Sales Revenue (Decrease Revenue / Sales Return)
                    [
                        'account_code' => $this->ledgerService->getCode($sale->business_id, 'Sales Revenue'),
                        'debit' => $totalReturnValue,
                        'credit' => 0
                    ],
                    // CREDIT Accounts Receivable (Decrease Asset - customer owes less)
                    [
                        'account_code' => $this->ledgerService->getCode($sale->business_id, 'Accounts Receivable'),
                        'debit' => 0,
                        'credit' => $totalReturnValue
                    ]
                ];

                $this->ledgerService->post(
                    $sale->business_id,
                    $entries,
                    $sale,
                    "Sales Return for Sale #{$sale->sale_number}",
                    $saleId,
                    'sales'
                );
            }

            // 3. Audit return
            AuditService::log(
                'sale_returned',
                'sales',
                'sales',
                $saleId,
                ['returned_items_count' => count($returnItems)]
            );

            return true;
        });
    }

    /**
     * Define the Double-Entry Posting Rules for a Sale
     */
    protected function recordFinancialEntry($sale)
    {
        $businessId = $sale->business_id;

        $entries = [
            // DEBIT Accounts Receivable (Increase Asset)
            [
                'account_code' => $this->ledgerService->getCode($businessId, 'Accounts Receivable'),
                'debit' => $sale->total,
                'credit' => 0
            ],
            // CREDIT Sales Revenue (Increase Revenue)
            [
                'account_code' => $this->ledgerService->getCode($businessId, 'Sales Revenue'),
                'debit' => 0,
                'credit' => $sale->total
            ]
        ];

        $this->ledgerService->post(
            $businessId,
            $entries,
            $sale,
            "Automated financial posting for Sale #{$sale->sale_number}"
        );
    }
}
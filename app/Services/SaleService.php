<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StockService;
use App\Services\AuditService;
use App\Services\LedgerService; 
use App\Services\ValuationService;
use App\Models\Sale; 
use App\Models\Warehouse;

class SaleService
{
    protected $ledgerService;
    protected $valuationService;

    public function __construct(LedgerService $ledgerService, ValuationService $valuationService)
    {
        $this->ledgerService = $ledgerService;
        $this->valuationService = $valuationService;
    }

    /**
     * Create a sale, consume stock batches, and post to ledger atomically.
     * Fixed: Added default data mapping to prevent "Undefined Index" errors.
     */
    public function create(array $saleData, array $items = [])
    {
        // If items are nested in $saleData (from Controller), extract them automatically
        if (empty($items) && isset($saleData['items'])) {
            $items = $saleData['items'];
        }

        return DB::transaction(function () use ($saleData, $items) {
            $user = Auth::user();
            $businessId = $saleData['business_id'] ?? $user->business_id;
            $locationId = $saleData['business_location_id'] ?? $user->business_location_id;
            
            $totalCogs = 0;

            // 1. Create sale header
            $saleId = DB::table('sales')->insertGetId([
                'business_id'          => $businessId,
                'business_location_id' => $locationId,
                'warehouse_id'         => $saleData['warehouse_id'],
                'cash_register_id'     => $saleData['cash_register_id'] ?? null,
                'sale_number'          => $saleData['sale_number'] ?? 'SL-' . strtoupper(uniqid()),
                'subtotal'             => $saleData['subtotal'],
                'discount'             => $saleData['discount'] ?? 0,
                'tax'                  => $saleData['tax'] ?? 0,
                'total'                => $saleData['total'],
                'status'               => 'completed', 
                'created_by'           => $user->id,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // 2. Create sale items & Compute COGS
            $reservationItems = [];
            foreach ($items as $item) {
                DB::table('sale_items')->insert([
                    'sale_id'     => $saleId,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['total_price'] ?? ($item['quantity'] * $item['unit_price']),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // Valuation Logic (FIFO/LIFO)
                $totalCogs += $this->valuationService->consumeStockAndGetCogs(
                    $businessId,
                    $saleData['warehouse_id'],
                    $item['product_id'],
                    $item['quantity']
                );

                $reservationItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ];
            }

            // 3. Update stock levels (Target 3)
            app(StockService::class)->reserve(
                $saleData['warehouse_id'],
                $reservationItems
            );

            // 4. Post to General Ledger
            $sale = Sale::find($saleId);
            $this->recordFinancialEntry($sale, $totalCogs);

            // 5. Audit
            AuditService::log('sale_created', 'sales', 'sales', $saleId, [
                'warehouse_id' => $saleData['warehouse_id'],
                'total'        => $saleData['total'],
                'cogs'         => $totalCogs
            ]);

            return $saleId;
        });
    }

    /**
     * Define the Double-Entry Posting Rules
     */
    protected function recordFinancialEntry($sale, $totalCogs)
    {
        $businessId = $sale->business_id;

        $entries = [
            // DEBIT Asset (Cash)
            [
                'account_code' => $this->ledgerService->getCode($businessId, 'Cash at Hand'),
                'debit' => $sale->total,
                'credit' => 0
            ],
            // CREDIT Revenue
            [
                'account_code' => $this->ledgerService->getCode($businessId, 'Sales Revenue'),
                'debit' => 0,
                'credit' => $sale->total
            ],
            // DEBIT COGS
            [
                'account_code' => $this->ledgerService->getCode($businessId, 'Cost of Goods Sold (COGS)'),
                'debit' => $totalCogs,
                'credit' => 0
            ],
            // CREDIT Inventory
            [
                'account_code' => $this->ledgerService->getCode($businessId, 'Inventory Asset'),
                'debit' => 0,
                'credit' => $totalCogs
            ]
        ];

        $this->ledgerService->post(
            $businessId,
            $entries,
            $sale,
            "Automated financial posting for Sale #{$sale->sale_number}"
        );
    }

    public function processReturn(int $saleId, array $returnItems)
    {
        return DB::transaction(function () use ($saleId, $returnItems) {
            $sale = Sale::findOrFail($saleId);
            $user = Auth::user();

            foreach ($returnItems as $item) {
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

                $totalReturnValue = $item['quantity'] * $item['unit_price'];
                $itemCost = DB::table('products')->where('id', $item['product_id'])->value('cost_price') ?? 0;
                $totalReturnCogs = $item['quantity'] * $itemCost;

                $entries = [
                    ['account_code' => $this->ledgerService->getCode($sale->business_id, 'Sales Revenue'), 'debit' => $totalReturnValue, 'credit' => 0],
                    ['account_code' => $this->ledgerService->getCode($sale->business_id, 'Cash at Hand'), 'debit' => 0, 'credit' => $totalReturnValue],
                    ['account_code' => $this->ledgerService->getCode($sale->business_id, 'Inventory Asset'), 'debit' => $totalReturnCogs, 'credit' => 0],
                    ['account_code' => $this->ledgerService->getCode($sale->business_id, 'Cost of Goods Sold (COGS)'), 'debit' => 0, 'credit' => $totalReturnCogs]
                ];

                $this->ledgerService->post($sale->business_id, $entries, $sale, "Sales Return reversal for Sale #{$sale->sale_number}");
            }

            AuditService::log('sale_returned', 'sales', 'sales', $saleId, ['items' => count($returnItems)]);
            return true;
        });
    }
}
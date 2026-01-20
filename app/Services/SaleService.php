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
     */
    public function create(array $saleData, array $items)
    {
        return DB::transaction(function () use ($saleData, $items) {

            $user = Auth::user();
            $totalCogs = 0;

            // 1. Create sale header (Target 6)
            $saleId = DB::table('sales')->insertGetId([
                'business_id'          => $saleData['business_id'],
                'business_location_id' => $saleData['business_location_id'],
                'warehouse_id'         => $saleData['warehouse_id'],
                'cash_register_id'     => $saleData['cash_register_id'] ?? null,
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

            // 2. Create sale items & Compute COGS via Valuation Strategy (Target 5)
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

                // Track actual cost based on FIFO/LIFO/Weighted Average batches
                $totalCogs += $this->valuationService->consumeStockAndGetCogs(
                    $saleData['business_id'],
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

            // 4. Post to General Ledger (Target 4 Automation)
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
     * Define the Double-Entry Posting Rules for a Sale (Revenue, Cash & COGS)
     */
    protected function recordFinancialEntry($sale, $totalCogs)
    {
        $businessId = $sale->business_id;

        // Logic: Since you use Cash Registers, we assume a Cash Sale.
        // If you allow Credit Sales, you would check a payment_method field here.
        $assetAccount = $this->ledgerService->getCode($businessId, 'Cash at Hand');

        $entries = [
            // DEBIT Asset (Increase Cash/Receivable)
            [
                'account_code' => $assetAccount,
                'debit' => $sale->total,
                'credit' => 0
            ],
            // CREDIT Revenue (Increase Sales Income)
            [
                'account_code' => $this->ledgerService->getCode($businessId, 'Sales Revenue'),
                'debit' => 0,
                'credit' => $sale->total
            ],
            // DEBIT COGS (Record Expense of stock sold)
            [
                'account_code' => $this->ledgerService->getCode($businessId, 'Cost of Goods Sold (COGS)'),
                'debit' => $totalCogs,
                'credit' => 0
            ],
            // CREDIT Inventory (Decrease Asset stock value)
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

    /**
     * Reverses financial impact (Revenue & COGS) and restores stock.
     */
    public function processReturn(int $saleId, array $returnItems)
    {
        return DB::transaction(function () use ($saleId, $returnItems) {
            $sale = Sale::findOrFail($saleId);
            $user = Auth::user();

            foreach ($returnItems as $item) {
                // 1. Restore stock
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

                // 2. Financial Reversal
                $totalReturnValue = $item['quantity'] * $item['unit_price'];
                $itemCost = DB::table('products')->where('id', $item['product_id'])->value('cost_price');
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
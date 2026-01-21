# CONTINUITY_MAP.md

**System Invariants, Lifecycle Rules & Locked Targets**

**Status:** FINAL
**Last Updated:** 2026-01-20

> **Authority Clause**
> This document defines the **non-negotiable invariants** governing the system.
> It **overrides all other documentation**, including `PROJECT_CONTEXT.md`, inline comments, developer assumptions, or UI behavior.
> Any implementation that violates an invariant herein is a **critical system bug**.

---

## LOCKED TARGET STATUS

**Targets Locked & Enforced:** 4, 5, 6
**Verified Through Tests:** Yes
**Regression Tolerance:** Zero

---

## 1. Financial Ledger Doctrine (Target 4)

The `ledger_entries` table is the **ultimate and sole source of financial truth**.

### 1.1 Balanced Ledger Rule (Double-Entry Invariant)

* No economically meaningful event (Sale, Expense, Return, Adjustment) may persist unless it produces a **balanced ledger posting**.
* **Invariant:**
  `SUM(debit) == SUM(credit)` for every unique `source_type + source_id`.
* **Enforcement:**
  All postings must be wrapped in a database transaction via `LedgerService`.
  Any imbalance MUST throw and rollback.

### 1.2 Ledger Immutability

* Ledger entries MUST NOT be updated or deleted.
* Errors are corrected **only via reversal postings**.
* This guarantees a permanent, tamper-proof audit trail.

### 1.3 System Account Integrity

* System accounts (Cash at Hand, Sales Revenue, COGS, Inventory Asset, Accounts Receivable, Accounts Payable) MUST be seeded per `business_id`.
* If `getCode()` is called and the account is missing:

  * The operation MUST fail (`ModelNotFoundException`).
  * Silent fallback is forbidden.

---

## 2. Inventory Lifecycle Invariants (Target 5)

Inventory follows a **strict, multi-phase lifecycle**.
Phases MUST NOT be collapsed, skipped, or reordered.

### 2.1 Reservation Phase (Sale Intent)

* `warehouse_stock.quantity` MUST NOT change.
* Only `warehouse_stock.reserved_quantity` may increase.
* Reservation MUST be atomic with Sale creation.
* No ledger posting occurs here except financial claim creation.

### 2.2 Commitment Phase (Fulfillment Execution)

* Physical stock MUST be deducted **exactly once**.
* Commitment MUST be:

  * Ledger-driven
  * Idempotent
* **FIFO Enforcement:**
  `ValuationService` MUST relieve stock from the **oldest `stock_batches` first**.
* Stock decrement without a corresponding **COGS ledger entry is forbidden**.

### 2.3 Recovery Phase (Returns & Reversals)

* Returns MUST restore physical stock via `StockService`.
* Original sale records MUST NOT be deleted.
* Returns MUST generate reversal ledger entries.
* Double-restoration is prevented by ledger and audit guards.

---

## 3. Separation of Duties (Sale vs Fulfillment)

* **Sale**

  * Creates financial obligation (Ledger)
  * Creates stock reservation
  * Does NOT move physical stock
* **Fulfillment**

  * Moves physical stock
  * Resolves reservation
* **Constraint:**
  No fulfillment may occur without a valid Sale or Transfer record.

---

## 4. Cash Management Invariants (Target 6)

### 4.1 Open Register Guard

* No Sale or Expense may be recorded unless:

  * `cash_register.status = open`
  * The register belongs to the acting `user_id`
* UI and API MUST enforce this before invoking services.

### 4.2 Dynamic Cash Reconciliation

* “Expected Cash” MUST NEVER be stored or manually editable.
* **Hard Formula:**

  ```
  Expected Cash = Opening Balance + Recorded Sales − Approved Expenses
  ```
* On register closure:

  * Variance (`actual − expected`) MUST be posted as a ledger entry.

### 4.3 Expense Governance

* Every expense MUST be linked to:

  * `user_id`
  * `cash_register_id`
* Expenses MUST pass approval before affecting reconciliation.
* Pending or rejected expenses MUST NOT influence cash math.

---

## 5. Fulfillment & Reconciliation State Machines

### 5.1 State Integrity

* **Warehouse Fulfillment:**
  `pending → approved → released → reconciled`
* **Offline Fulfillment:**
  `pending → approved → reconciled`
* Terminal states (`reconciled`, `conflicted`) are **immutable**.

### 5.2 Offline Reconciliation

* Fulfillment tokens MUST be cryptographically signed (Target 7).
* Signature failures require supervisor override.
* Reconciliation MUST be idempotent:

  * Guarded by fulfillment state
  * Guarded by ledger existence

---

## 6. Posting Rules (Domain Invariants)

| Event                | Debit Account       | Credit Account      |
| -------------------- | ------------------- | ------------------- |
| Sale (Cash)          | Cash at Hand        | Sales Revenue       |
| Sale (On Credit)     | Accounts Receivable | Sales Revenue       |
| Purchase (On Credit) | Inventory Asset     | Accounts Payable    |
| Supplier Payment     | Accounts Payable    | Cash / Bank         |
| Sale Return          | Sales Revenue       | Accounts Receivable |

---

## 7. Operational Invariants

### 7.1 Immutability of Finalized States

* Completed Sales are immutable.
* Closed Cash Registers are immutable.
* Corrections occur via returns or reversal flows only.

### 7.2 Mandatory Attribution

Every row in:

* `sales`
* `expenses`
* `ledger_entries`
* `audit_logs`

MUST include:

* `user_id` (Who)
* `business_location_id` (Where)
* `created_at` (When)

---

## 8. Idempotency Guarantees

| Operation         | Idempotent | Enforcement Mechanism            |
| ----------------- | ---------- | -------------------------------- |
| Stock Reservation | ❌          | Transaction boundary             |
| Financial Posting | ✅          | `source_type + source_id` unique |
| Stock Commitment  | ✅          | Ledger guard                     |
| Sales Return      | ✅          | Audit log + Ledger validation    |

---

## 9. Testing as Governance

The following tests are **critical path**.
Failure equals **system integrity breach**:

1. `LedgerAutomationTest`
2. `SaleLedgerAutomationTest`
3. `CashReconciliationTest`
4. `CashRegisterReconciliationTest`
5. Offline Fulfillment Reconciliation Tests

**Total Coverage:** 32 integration tests — ALL PASS

---

## 10. Completed Milestones

* FIFO Profit & Loss (Target 5)
* Cashier Reconciliation (Target 6)
* Cryptographically Signed QR Logistics (Targets 7/8)
* Inventory Valuation Engine (Target 10)
* Supplier Debt & Partial Payments (Target 12)

---

### FINAL DECLARATION

This document represents the **constitutional law** of the system.
All future features, refactors, and optimizations MUST conform to these invariants.

Any deviation is not a feature gap — it is a defect.

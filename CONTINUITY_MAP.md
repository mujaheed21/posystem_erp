# CONTINUITY_MAP — SYSTEM INVARIANTS & MILESTONES

This document defines the **non-negotiable rules** that govern the behavior of the system. These rules are enforced by code and protected by automated tests.

> [!IMPORTANT]
> **This document overrides all other documentation**, including `PROJECT_CONTEXT.md`, comments, or developer assumptions. If an implementation violates an invariant defined here, it is considered a critical bug.

---

## 1. Inventory Lifecycle Invariants

Inventory handling is governed by a strict, multi-phase lifecycle. These phases **must never be collapsed or reordered**.

### 1.1 Reservation Phase (Sale Intent)
* Physical stock quantity (`warehouse_stock.quantity`) MUST NOT change.
* Only `warehouse_stock.reserved_quantity` may increase.
* Reservation MUST be atomic with sale creation.

### 1.2 Commitment Phase (Fulfillment Execution)
* Stock MUST be deducted exactly once per fulfillment.
* Commitment MUST be ledger-driven and idempotent.
* **FIFO Enforcement:** Stock deduction must target the oldest available `StockBatch` first to maintain valuation accuracy.

### 1.3 Recovery Phase (Returns/Reversals)
* Returns MUST restore physical stock (`quantity`) via `StockService`.
* Returns MUST NOT delete original sale records; they MUST create reversal entries.
* Returns MUST be ledger-guarded to prevent double-restoration.

---

## 2. Financial Ledger Doctrine

The `ledger_entries` table is the **ultimate source of truth** for the financial state of the business.

### 2.1 Double-Entry Invariant
* Every financial event MUST post at least one Debit and one Credit.
* **The sum of Debits MUST equal the sum of Credits for every transaction.**
* Out-of-balance postings MUST be rejected at the database transaction level.



### 2.2 Immutability Invariant
* Financial ledger entries MUST NOT be updated or deleted.
* Errors in posting MUST be corrected via **Reversal Postings** (New entries that offset the error).
* This ensures a permanent, tamper-proof audit trail for all Kano market transactions.

---

## 3. Posting Rules (Domain Invariants)

| Event | Debit Account | Credit Account |
| :--- | :--- | :--- |
| **Sale (On Credit)** | Accounts Receivable | Sales Revenue |
| **Purchase (On Credit)** | Inventory Asset | Accounts Payable |
| **Payment (to Supplier)**| Accounts Payable | Cash/Bank Asset |
| **Sale Return** | Sales Revenue | Accounts Receivable |

---

## 4. Fulfillment & Reconciliation Invariants

### 4.1 State Machine Integrity
* **Warehouse Fulfillment**: `pending → approved → released → reconciled`.
* **Offline Fulfillment**: `pending → approved → reconciled`.
* Terminal states (`reconciled`, `conflicted`) MUST NOT be reversed.

### 4.2 Offline Reconciliation
* Supervisor override MUST be enforced for cryptographic signature failures (Target 8).
* Reconciliation MUST be idempotent: Checked against both the `fulfillment_status` and `ledger_entries`.

---

## 5. Audit & Traceability

* Every Ledger entry MUST link to a `source_type` and `source_id`.
* Every Stock movement MUST link to an `audit_logs` entry.
* System-wide "Hard Separation": Operations (Services) propose values; the Ledger Engine validates and locks them.



---

## 6. Completed Milestones (Targets 5-12)

The following core features have been implemented and verified against the above invariants:

* **FIFO Profit & Loss (Target 5):** Real-time COGS tracking based on batch costs.
* **Cashier Reconciliation (Target 6):** Shift-based cash accountability.
* **QR Logistics (Target 7/8):** Cryptographically signed transfers between Warehouse and Stalls.
* **Inventory Valuation (Target 10):** Total Naira value of stock-at-rest and stock-in-transit.
* **Supplier Debt Tracking (Target 12):** Partial payment logic and payment status automation.

---

## 7. Idempotency Guarantees

| Operation | Idempotent | Enforcement Mechanism |
| :--- | :--- | :--- |
| Stock reservation | ❌ | Transaction boundary |
| Financial Posting | ✅ | Source-Type/ID unique check |
| Stock commitment | ✅ | Ledger uniqueness |
| Sales Return | ✅ | Audit log + Ledger guard |

---

## 8. Enforcement

These invariants are enforced by:
* Database foreign key constraints.
* Ledger balance validation logic.
* **Automated Integration Tests:** 32 passing tests verify these behaviors.

---
**Document Status: FINAL (Updated 2026-01-19)**
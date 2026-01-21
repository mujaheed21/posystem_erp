# PROJECT_CONTEXT.md

**Updated: 2026-01-20**

## Secure Multi-Location POS, Warehouse Fulfillment & Double-Entry Accounting System

**Domain Context: Kano ERP Ecosystem**

---

## 1. Purpose of This Document

This document defines the **strategic intent, operational philosophy, environmental assumptions, current progress, and evolution boundaries** of the system.

It exists to explain:

* **Why** the system behaves the way it does
* **What problems it is explicitly designed to solve**
* **How future development must remain aligned with core integrity goals**

This document is **not a rulebook**.

> All non-negotiable rules, invariants, lifecycle constraints, and enforcement logic are defined in `CONTINUITY_MAP.md`.
> Where conflict exists, the **Continuity Map always overrides this document**.

---

## 2. System Identity

This is not a conventional POS.

It is a **high-integrity trading, inventory, and financial control platform** designed for environments where:

* Trust is limited
* Transaction volume is high
* Credit is normal
* Connectivity is unreliable
* Inventory leakage is existential

Its mission is to **close the trust gap** between:

* Physical stock
* Financial truth
* Human operations

The system behaves like a **financial guardian**, not a retail app.

---

## 3. Core Philosophy

### 3.1 Integrity Over Convenience

Any operation that cannot be:

* Attributed to a human
* Scoped to a location and warehouse
* Linked to a financial or inventory source
* Audited after the fact

**must fail**, even if this slows down operations.

Correctness > Speed
Traceability > Comfort

---

### 3.2 Explicit Authority, Not Implicit Trust

Roles grant **access**, not authority.

Authority is exercised:

* Per event
* Under explicit permissions
* With irreversible actions logged

No action is safe by seniority, habit, or convenience.

---

### 3.3 State Is Truth

* Persisted **state** is the only business truth
* State transitions are controlled and forward-only
* Partial execution is treated as failure

Transport-level `status` values exist **only for communication**, never for truth.

---

## 4. Operational Environment: Kano Market Reality

### 4.1 Asynchronous Commerce

Sales occur at stalls, shops, and front desks.
Fulfillment occurs at warehouses and depots.

The system formalizes this using:

**Reservation → Fulfillment → Reconciliation**

Sales capture **money and reservation**, not stock movement.

---

### 4.2 Offline-First Reality

Connectivity and power are unreliable.

Offline fulfillment is therefore **first-class**, not a fallback.

Cryptographically signed QR tokens provide:

* Authenticity
* Replay protection
* Expiry enforcement
* Warehouse binding

Offline actions are **pending and reviewable** until reconciliation.

---

### 4.3 Credit-Driven Trade

Debt is normal.

The system treats:

* Accounts Receivable
* Accounts Payable
* Outstanding balances

as first-class financial entities with full ledger rigor.

---

## 5. Inventory as a Financial Asset

Inventory and money are two representations of the same value.

A bag of goods is simultaneously:

* Physical stock
* A financial asset

---

### 5.1 FIFO Inventory Engine

* Stock is batch-specific
* FIFO costing is mandatory
* Oldest stock is relieved first
* COGS is always explainable

The system can report:

* Capital locked in stock
* Stock by warehouse
* Stock in transit

---

### 5.2 Stock as a Ledger, Not a Counter

Stock is **never incremented or decremented silently**.

Every movement is:

* Ledger-based
* Idempotent
* Auditable

Silent mutation is forbidden.

---

## 6. Double-Entry Financial Ledger

### 6.1 Fundamental Principle

> **Debits must always equal Credits.**
> If they do not, the operation is rejected.

---

### 6.2 Canonical Events

**Purchase**

* Debit: Inventory Asset
* Credit: Accounts Payable

**Payment**

* Debit: Accounts Payable
* Credit: Cash / Bank

**Sale**

* Debit: Accounts Receivable or Cash
* Credit: Revenue

**COGS Recognition**

* Debit: Cost of Goods Sold
* Credit: Inventory Asset

---

### 6.3 Derived Financial Status

Payment status is **calculated**, never manually set.

Paid / Partial / Unpaid are derived from ledger totals.

---

## 7. Fulfillment Architecture

### 7.1 Sale ≠ Fulfillment

A sale does **not** move stock.

Fulfillment is explicit, authorized, and verifiable.

This prevents:

* Ghost inventory loss
* Post-hoc manipulation
* Silent overrides

---

### 7.2 Online Fulfillment

* Single-use tokens
* Row locking
* Permission enforcement

Replay and parallel execution are deterministically rejected.

---

### 7.3 Offline Fulfillment

* Cryptographic verification
* Pending records only
* No final stock mutation

Final stock deduction occurs **only during supervised reconciliation**.

---

## 8. Security & Accountability Model

### 8.1 Propose vs Lock

* Services **propose** outcomes
* State machines and ledger engines **lock truth**

No service may directly mutate irreversible state.

---

### 8.2 Mandatory Traceability

Every record must capture:

* Who
* Where
* Why

Anonymous or context-free records are invalid.

---

## 9. Governance Through Testing

Tests are governance.

Feature tests assert:

* Lifecycle correctness
* Security invariants
* Audit guarantees

A failing test represents a **policy violation**, not a bug.

---

## 10. Scope Boundaries

### In Scope

* Multi-warehouse inventory
* FIFO costing
* QR-based fulfillment
* Offline reconciliation
* Double-entry ledger
* Audit logging
* Supervisor override documentation

### Out of Scope

* Payroll and HR
* Manufacturing workflows
* Automated regulatory filing
* Fraud adjudication

---

## 11. Current Progress & Verified Targets

### ✅ Target 3: Multi-Location Stock Tracking

* Warehouse-aware stock
* Physical vs Reserved separation

### ✅ Target 4: General Ledger Automation

* Balanced debit/credit enforced
* “No Account, No Entry” policy

### ✅ Target 5: FIFO Inventory Valuation

* Batch-level COGS
* Real-time gross profit

### ✅ Target 6: Secure Cash Management

* Register lifecycle (Open → Close)
* Dynamic expected cash
* Variance locking

---

## 12. Cash & Expense Engine (Target 6 – VERIFIED)

### Cash Register Lifecycle

Expected Cash = Opening + Sales − Expenses

Variance is locked at closure.

### Expense Governance

* ≤ ₦5,000: Auto-approved
* > ₦5,000: Supervisor approval required
* Full attribution and audit trail enforced

---

## 13. Technical Architecture Reality

### Core Services

* `LedgerService` — Double-entry enforcement
* `SaleService` — Transaction orchestration
* `ValuationService` — FIFO engine
* `CashRegisterService` — Cash control
* `StockExpenseService` — Cash-out audit

### Data Integrity

* `DECIMAL(15,2)` everywhere
* Source-linked ledger entries
* Feature-test guarded

---

## 14. Account Mapping Infrastructure

Standard COA enforced:

* Assets: Cash, Bank, Inventory
* Liabilities: Payables
* Equity: Capital
* Revenue: Sales
* Expenses: Transport, Rent, Security

---

## 15. Evolution Expectations

The system may evolve in:

* UI/UX
* Performance
* Reporting
* Integrations

It must **never evolve** toward:

* Implicit trust
* Weakened audits
* Silent state mutation
* Convenience over integrity

---

## 16. Final Governance Statement

As of **January 20, 2026**, the backend financial, inventory, and fulfillment engines are **verified, versioned, and governed**.

No future UI, API, or integration is permitted to bypass:

* State machines
* Ledger enforcement
* Audit guarantees

Anything less constitutes **system failure**.

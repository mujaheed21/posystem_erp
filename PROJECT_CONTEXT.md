# PROJECT CONTEXT

## Secure Multi-Location POS, Warehouse Fulfillment & Double-Entry Accounting System

**Domain Context: Kano ERP Ecosystem**

---

## 1. Purpose of This Document

This document defines the **strategic intent, operational philosophy, environmental assumptions, and evolution boundaries** of the system.

It exists to explain:

* **Why** the system behaves the way it does
* **What problems it is explicitly designed to solve**
* **How future development must remain aligned with core integrity goals**

This document is *not* a rulebook.

> All non-negotiable rules, invariants, lifecycle constraints, and enforcement logic are defined in `CONTINUITY_MAP.md`. Where any ambiguity or conflict exists, the Continuity Map **always overrides** this document.

---

## 2. System Identity

This system is not a conventional POS.

It is a **high-integrity trading and financial control platform** designed for environments where:

* Trust is limited
* Volume is high
* Credit is common
* Connectivity is unreliable
* Inventory leakage is existential

The system’s core mission is to **close the trust gap** between:

* Physical stock
* Financial truth
* Human operations

---

## 3. Core Philosophy

### 3.1 Integrity Over Convenience

The system intentionally rejects shortcuts.

Any operation that cannot be:

* Attributed to a human
* Scoped to a location and warehouse
* Linked to a financial or inventory source
* Audited after the fact

**must fail**, even if this slows down operations.

Correctness is prioritized over speed.
Traceability is prioritized over comfort.

---

### 3.2 Explicit Authority, Not Implicit Trust

Roles grant **access**, not authority.

Authority is exercised:

* Per event
* Under explicit permissions
* With irreversible actions logged

No user action is considered safe simply because of seniority, habit, or convenience.

---

### 3.3 State Is Truth

The system treats **state** as the only source of persisted truth.

* State transitions are controlled
* Backward transitions are forbidden
* Partial execution is treated as failure

Transport-level `status` values exist only for communication and must never represent business truth.

---

## 4. Operational Environment: Kano Market Reality

The system is designed around real constraints of large-scale Nigerian trading environments, especially Kano:

### 4.1 Asynchronous Commerce

Sales frequently occur at:

* Stalls
* Shops
* Front desks

While fulfillment occurs at:

* Main warehouses
* Remote depots

The system formalizes this reality using a **Reservation → Fulfillment → Reconciliation** lifecycle instead of pretending that sales and stock movement are simultaneous.

---

### 4.2 Offline-First Reality

Connectivity is unreliable.

The system assumes:

* Local network outages
* Power instability
* Temporary device isolation

Offline fulfillment is therefore a **first-class feature**, not a fallback hack.

Cryptographically signed QR codes provide:

* Authenticity
* Replay protection
* Expiry enforcement
* Warehouse binding

Offline actions are never final. They are **pending, reviewable, and reversible** until reconciliation.

---

### 4.3 Credit-Driven Trade

Debt is normal, not exceptional.

Suppliers extend credit.
Customers buy on account.

The system treats:

* Payables
* Receivables
* Outstanding balances

as first-class entities that must be tracked with the same rigor as physical stock.

---

## 5. Inventory as a Financial Asset

The system treats inventory and money as two representations of the same underlying value.

A bag of goods in a warehouse is simultaneously:

* Physical stock
* A financial asset

---

### 5.1 FIFO Inventory Engine

Stock is **never generic**.

Every quantity belongs to a specific batch derived from a purchase event.

Key principles:

* FIFO costing is mandatory
* Oldest stock is always relieved first
* Stock valuation is always explainable

The system can provide snapshots of:

* Capital locked in stock
* Stock by warehouse
* Stock in transit

---

### 5.2 Stock as a Ledger, Not a Counter

Stock is not updated by incrementing numbers.

Every movement is:

* Ledger-based
* Idempotent
* Auditable

Silent stock mutation is forbidden.

---

## 6. Double-Entry Financial Ledger

Every economically meaningful event produces **balanced financial entries**.

### 6.1 Fundamental Principle

> Debits must always equal Credits.

If they do not, the system refuses to commit the operation.

---

### 6.2 Canonical Events

**Purchase Event**

* Debit: Inventory Asset
* Credit: Accounts Payable

**Payment Event**

* Debit: Accounts Payable
* Credit: Cash / Bank

**Sale Event**

* Debit: Accounts Receivable or Cash
* Credit: Revenue

**COGS Recognition**

* Debit: Cost of Goods Sold
* Credit: Inventory Asset

---

### 6.3 Automated Financial Status

Payment status is **derived**, never manually set.

The system calculates:

* Unpaid
* Partial
* Paid

by comparing ledger totals, not flags.

---

## 7. Fulfillment Architecture

### 7.1 Separation of Sale and Fulfillment

A sale does **not** move stock.

Fulfillment is:

* Explicit
* Verifiable
* Authorized

This prevents:

* Ghost inventory loss
* Post-hoc manipulation
* Silent overrides

---

### 7.2 Online Fulfillment

Online fulfillment uses:

* Single-use tokens
* Row locking
* Permission checks

Replay, parallel execution, and token reuse are deterministically rejected.

---

### 7.3 Offline Fulfillment

Offline fulfillment:

* Verifies cryptographic signatures
* Creates pending records
* Does not mutate final state

Final stock deduction occurs only during supervised reconciliation.

---

## 8. Security & Accountability Model

### 8.1 Propose vs Lock

The system enforces a strict boundary:

* **Services propose outcomes**
* **State machines and ledger engines lock truth**

No service may directly mutate irreversible state.

---

### 8.2 Mandatory Traceability

Every record must capture:

* Who performed the action
* Where it occurred
* Why it occurred

Anonymous or context-free records are invalid.

---

## 9. Governance Through Testing

Tests are part of the governance model.

Feature tests assert:

* Security invariants
* Lifecycle correctness
* Audit guarantees

A change that breaks a test is considered a **policy violation**, not a bug.

---

## 10. Scope Boundaries

### 10.1 In Scope

* Multi-warehouse inventory
* FIFO costing
* QR-based fulfillment
* Offline reconciliation
* Double-entry ledger
* Audit logging
* Supervisor override documentation

---

### 10.2 Out of Scope

* Payroll and HR
* Manufacturing workflows
* Automatic regulatory filing
* Fraud adjudication

---

## 11. Evolution Expectations

The system is expected to evolve in:

* UI/UX
* Performance
* Reporting
* Integrations

It must **never evolve** toward:

* Implicit trust
* Weakened audit trails
* Silent state mutation
* Convenience over integrity

---

## 12. Final Statement

This system is designed to behave like a **financial guardian**, not a retail app.

For every unit of stock moved and every unit of currency exchanged, the system guarantees:

* Traceability
* Balance
* Accountability

Anything less is considered system failure.

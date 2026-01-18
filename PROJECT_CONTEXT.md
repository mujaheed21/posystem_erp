# PROJECT CONTEXT

**Secure Multi-Location POS, Warehouse Fulfillment & Double-Entry Accounting System**

This document defines the **scope, intent, assumptions, and expected evolution** of the system.
It provides contextual guidance for developers, auditors, and stakeholders while deferring all
non-negotiable rules and invariants to `CONTINUITY_MAP.md`.

---

## 1. System Purpose

The system is designed to:

* Support multi-location point-of-sale operations.
* Enable warehouse-backed fulfillment workflows.
* **Maintain a high-integrity General Ledger via Double-Entry Accounting.**
* Preserve inventory and financial integrity under concurrency and partial failure.
* Enforce accountability for high-risk operational and financial decisions.

The system prioritizes:

* Correctness over speed.
* **Financial Truth (Balanced Books) over operational convenience.**
* Explicit lifecycle control over implicit side-effects.

---

## 2. Operational Environment (Updated)

The system operates in environments where:

* Network connectivity may be intermittent (Offline QR Fulfillment).
* Sales and fulfillment may occur in different locations.
* **Financial accuracy is as critical as inventory accuracy.**
* Auditability is required for internal or regulatory reasons.

These realities justify:

* Separation of sale creation and stock deduction.
* **Immutable Ledger Entries: No deletion or editing of financial transactions.**
* Explicit state machines for both Stock and Cash movement.
* Ledger-based accounting of stock and monetary value.

---

## 3. Inventory & Financial Philosophy

The system treats Inventory and Finance as two sides of the same coin. Every physical movement has a corresponding financial impact.

### 3.1 Stock Handling
* **Sales express intent**
* **Fulfillment authorizes execution**
* **Reconciliation confirms completion**

### 3.2 Financial Posting
The system utilizes a **Double-Entry Engine** where:
* **Purchases** increase Inventory Assets and Accounts Payable.
* **Sales** increase Accounts Receivable and Sales Revenue.
* **Payments** settle liabilities and decrease Cash Assets.
* **Returns** utilize reversal postings to maintain a clean audit trail.



---

## 4. Security & Accountability

### 4.1 Trust Model
* Authority is exercised per event, not per role.
* Financial events are immutable once posted.

### 4.2 Audit Attribution
Every ledger entry and stock movement is attributed to:
* **User**: Who performed the action.
* **Source**: The specific Sale, Purchase, or Payment ID.
* **Business**: The specific entity owning the data.

---

## 5. Ledger Integration Context

The system enforces a **Hard Separation** between operations and finance. Services (SaleService, PurchaseService) execute business logic and then "request" the LedgerService to record the result. This ensures:
* Operations can evolve without breaking accounting rules.
* The General Ledger remains the "Single Source of Truth."



---

## 6. Scope Boundaries

### 6.1 In Scope
* Sale & Purchase lifecycle management.
* Stock reservation and commitment.
* **Double-Entry Ledger System (General Ledger).**
* **Chart of Accounts (COA) management.**
* **Settlement & Payment workflows (Customer/Supplier).**
* **Sales Return & Reversal logic.**
* Offline fulfillment queues & reconciliation.
* Audit logging and Supervisor override enforcement.

### 6.2 Out of Scope
* Automated fraud adjudication.
* Real-time regulatory reporting.
* Human resource management.
* Payroll or complex tax compliance.

---

## 7. Testing & Governance (Updated)

Testing is the primary safeguard against "Policy Drift." The project utilizes **Feature Integration Tests** to protect:
* **Financial Balance**: Ensuring Debits always equal Credits.
* **Stock Integrity**: Ensuring physical stock doesn't "leak" during returns or sales.
* **Lifecycle State**: Ensuring transactions cannot skip necessary security steps.

---

## 8. Summary Statement

> This system bridges the gap between operational POS reality and formal financial accounting. 
> By utilizing a multi-phase fulfillment lifecycle and a self-balancing double-entry ledger, 
> it prevents the silent integrity failures common in traditional retail software.
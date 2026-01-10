# Continuity Map

**Secure Multi-Location POS & Warehouse Fulfillment System**

This document defines the **authoritative continuity contract** between models, services, database schema, and tests.
Any future change must preserve these invariants unless explicitly refactored across all layers.

---
## 1. Core Domain Invariants (Non-Negotiable)

### 1.1 Business

* A **Business** is the root ownership entity.
* All warehouses, products, sales, and users belong to **exactly one business**.
* `business_id` is **mandatory** everywhere it appears.

---

### 1.2 Warehouse

* A **Warehouse** belongs to one Business.
* Warehouses are the **only stock-holding entities**.
* Fulfillment always reduces stock **from a warehouse**, never directly from a business.

**Key fields**

* `id`
* `business_id`

---

### 1.3 User

* A **User** belongs to a Business.
* A user may act in the context of **one warehouse at a time** during fulfillment.
* Warehouse context is **runtime-bound**, not permanently relational.

**Authorization**

* Fulfillment requires `warehouse.fulfill` permission.
* Authentication is enforced before any fulfillment logic.

---

## 2. Sales & Fulfillment Continuity

### 2.1 Sale

A **Sale** represents a completed POS transaction awaiting warehouse fulfillment.

**Mandatory fields**

* `business_id`
* `business_location_id` *(logical grouping only; no model dependency)*
* `warehouse_id`
* `sale_number`
* `created_by`

**Invariant**

> A sale **must be fully self-describing** without inferred defaults.

---

### 2.2 Sale Items

Each sale contains one or more immutable sale items.

**Mandatory fields**

* `sale_id`
* `product_id`
* `quantity`
* `unit_price`
* `total_price`

**Invariant**

> Sale items are the **source of truth** for fulfillment hashing.

---

### 2.3 Fulfillment Token

* Tokens are **single-use**
* Tokens are **hash-verified**
* Tokens are **time-bound**
* Tokens are **sale-bound**

**Failure modes**

* invalid
* expired
* reused
* tampered sale items

All failures must be **explicit and deterministic**.

---

## 3. Stock Engine Continuity

### 3.1 Warehouse Stock

Represents current available stock.

**Unique constraint**

```
(warehouse_id, product_id)
```

**Invariant**

> Stock rows are **never duplicated** — only updated.

---

### 3.2 Stock Movement

Every stock change **must** generate a movement record.

**Mandatory fields**

* `business_id`
* `warehouse_id`
* `product_id`
* `type` (ENUM)
* `quantity` (signed)
* `reference_type`
* `reference_id`
* `created_by`

**Allowed `type` ENUM values**

* `sale`
* `offline_reconciliation`

**Invariant**

> Stock cannot change without an auditable movement.

---

## 4. Offline Fulfillment Continuity

### 4.1 OfflineFulfillmentPending

Represents fulfillment performed **without live validation**, pending reconciliation.

**States**

* `pending`
* `approved`
* `reconciled`
* `rejected`

**Invariant**

> Reconciliation is **one-time and irreversible**.

---

### 4.2 Offline Reconciliation

* Requires **approval**
* Must be **idempotent**
* Must:

  * Decrease stock
  * Write stock movements
  * Write audit log
  * Mark record reconciled

---

## 5. Audit Continuity

### 5.1 Audit Log

All sensitive actions must emit an audit entry.

**Required fields**

* `action`
* `user_id`
* `auditable_type`
* `auditable_id`
* `metadata`

**Key Actions**

* `offline_fulfillment_reconciled`
* `warehouse_fulfillment_verified`

---

## 6. Test Continuity Rules

### 6.1 Test Helpers

* Tests must **explicitly seed all required DB fields**
* No test may rely on:

  * DB defaults
  * Hidden migrations
  * Phantom models (e.g. `BusinessLocation`)

---

### 6.2 Feature Tests

Each feature test must validate:

* Authentication
* Authorization
* Business scoping
* Idempotency
* Audit trail

**If a test passes without asserting these, it is incomplete.**

---

## 7. Forbidden Assumptions (Do Not Re-Introduce)

❌ Implicit business location models
❌ Nullable foreign keys for core flows
❌ Silent enum coercion
❌ Stock mutation without movement
❌ Reusable fulfillment tokens

---

## 8. Change Protocol

Any change that touches:

* sales
* fulfillment
* stock
* reconciliation

**Must update**

1. Service logic
2. Tests
3. This continuity map

Failure to do so is a **breaking change**.

---
## 9. Fulfillment State Machine Continuity

**Canonical States:**

  * pending → approved → released → reconciled
  * conflicted (terminal)

**State Rules:**
•	No backward transitions
•	No skipped transitions
•	reconciled and conflicted are terminal
•	All state changes occur inside a database transaction
•	State transitions are the only path to finalization

**Database Guards:**
•	warehouse_fulfillments.sale_id is UNIQUE
•	warehouse_fulfillments.version enforces optimistic locking
•	Fulfillment rows are locked during transitions
•	Fulfillment tokens are locked during verification

**Stock Continuity:**
•	Stock movement is scoped to:
•	reference_type = sales
•	reference_id   = sale_id
•	(reference_type, reference_id, product_id) is UNIQUE
•	Duplicate stock deductions are rejected at DB level

**Guarantees:**
•	Double fulfillment is structurally impossible
•	Concurrent scans resolve safely without race conditions
•	Retries and job replays are idempotent
•	Stock cannot mutate without a final fulfillment state
•	Conflicted executions are detectable and reviewable

**Invariant:**
Fulfillment is atomic, final, and auditable.
No stock change may occur outside a reconciled fulfillment





**Status:**
✅ All current feature and unit tests passing
✅ Continuity validated end-to-end



4️⃣ Canonical rule (this ends the inconsistency)
From now on, apply this non-negotiable rule:
✅ Use state when ALL are true:
Finite set of allowed values
Explicit allowed transitions
Terminal states exist
Concurrency matters
Idempotency matters
Reconciliation / approval involved

Examples
offline_fulfillment_pendings
warehouse_fulfillments
inventory_adjustments (future)
returns_workflows (future)

✅ Use status when ANY are true:
Descriptive / reporting-oriented
Linear progression
No strict transition enforcement
No concurrency locks required

Examples
sales
purchases
purchase_receipts
stock_transfers
# CONTINUITY MAP — SYSTEM INVARIANTS

This document defines the **non-negotiable rules** that govern the behavior of the system.
These rules are enforced by code and protected by automated tests.

Where conflicts arise:

* **This document overrides all other documentation**
* Including `PROJECT_CONTEXT.md`, comments, or developer assumptions

---

## 1. Inventory Lifecycle Invariants

Inventory handling is governed by a strict, multi-phase lifecycle. These phases **must never be collapsed or reordered**.

### 1.1 Reservation Phase

**Trigger:** Sale creation

**Invariants:**

* Physical stock quantity (`warehouse_stock.quantity`) MUST NOT change
* Only `warehouse_stock.reserved_quantity` may increase
* Reservation MUST fail if available stock is insufficient
* Reservation MUST be atomic with sale creation

Any physical stock deduction during reservation is a **critical violation**.

---

### 1.2 Commitment Phase

**Trigger:** Fulfillment state transitions to `released`

**Invariants:**

* Stock MUST be deducted exactly once per fulfillment
* Reserved stock MUST be cleared before or during physical deduction
* Commitment MUST be idempotent
* Commitment MUST be ledger-driven

**Source of Truth:**

```
stock_movements(reference_type, reference_id)
```

If a ledger entry exists, commitment MUST NOT run again.

---

### 1.3 Reconciliation Phase

**Trigger:** Fulfillment state transitions to `reconciled`

**Invariants:**

* Reconciliation MUST NOT mutate stock
* Reconciliation MUST be auditable
* Reconciliation is terminal

Any stock mutation during reconciliation is a **critical violation**.

---

## 2. Ledger Doctrine

The `stock_movements` table is the **single source of truth** for all stock mutations.

**Rules:**

* No stock deduction may occur without a corresponding ledger entry
* Ledger entries define idempotency
* Flags, counters, or booleans MUST NOT replace ledger checks

---

## 3. Fulfillment State Machine Invariants

### 3.1 Warehouse Fulfillment

**Valid States:**

```
pending → approved → released → reconciled
```

**Invariants:**

* Only `released` may trigger stock commitment
* `reconciled` and `conflicted` are terminal
* State machines MUST NOT mutate stock directly

---

### 3.2 Offline Fulfillment

**Valid States:**

```
pending → approved → reconciled
```

**Invariants:**

* Supervisor override MUST be enforced when required
* Reconciliation MUST be idempotent
* Reconciliation MUST emit an audit log
* Stock deduction MUST be ledger-guarded

---

## 4. Audit Invariants

* All stock mutations MUST be auditable via the ledger
* Business events MUST be logged to `audit_logs`
* Conflicts MUST be logged and MUST NOT silently fail

---

## 5. Idempotency Guarantees

| Operation              | Idempotent | Enforcement Mechanism  |
| ---------------------- | ---------- | ---------------------- |
| Stock reservation      | ❌          | Transaction boundary   |
| Stock commitment       | ✅          | Ledger uniqueness      |
| Fulfillment release    | ✅          | Ledger existence check |
| Offline reconciliation | ✅          | Ledger + state guard   |

---

## 6. Enforcement

These invariants are enforced by:

* Database constraints
* Explicit state machines
* Ledger checks
* Automated tests

If any invariant is violated:

> **The implementation is incorrect and MUST be fixed.**

---

**Document Status:** FINAL
  
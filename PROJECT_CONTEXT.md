# PROJECT CONTEXT

## Project Name

**Secure Multi-Location POS & Warehouse Fulfillment System**

---

## Purpose

This project is a **security-first, transaction-driven POS and warehouse fulfillment platform** designed to guarantee:

* Correct stock movement
* Explicit authorization
* Single-use fulfillment
* Deterministic state transitions
* Complete auditability

The system is intentionally engineered to **prevent by design**:

* Double fulfillment
* Stock desynchronization
* Unauthorized warehouse actions
* Silent or unaudited inventory changes
* Nondeterministic behavior that breaks tests

This is not a CRUD POS.
It is a **fulfillment engine with strict invariants**.

---

## Core Invariants (Non-Negotiable)

### 1. Stock Is a Ledger, Not a Counter

* Stock **must never** be mutated directly.
* All stock changes go through `StockService`.
* Every change produces a `stock_movements` record.
* Idempotency is enforced at the service level.

### 2. Fulfillment Is Single-Use and Transactional

* QR / online fulfillment tokens:

  * Are single-use
  * Are row-locked (`SELECT … FOR UPDATE`)
  * Become invalid immediately after use or expiry
* Offline fulfillments:

  * Require explicit approval
  * Can be reconciled once and only once
  * Cannot be replayed or retried silently

### 3. Authorization Is Explicit

* `warehouse.fulfill` is required for any fulfillment action
* `offline.fulfillment.approve` is required for offline approval
* No implicit role or fallback authorization exists

### 4. Lifecycle State Is Explicit and Singular

* **`state` is the only persisted lifecycle field**
* `status` is reserved strictly for API response semantics
* Lifecycle transitions are forward-only and terminal when completed

### 5. Audit Is Mandatory for Lifecycle Events

* Every lifecycle transition that finalizes fulfillment is audited
* No reconciliation or stock mutation may occur without an audit trail

---

## Domain Language (Strict)

| Term           | Meaning                                  |
| -------------- | ---------------------------------------- |
| `state`        | Persisted lifecycle state (domain truth) |
| `status`       | API / transport response indicator only  |
| Fulfillment    | A controlled, single-use stock operation |
| Reconciliation | Finalization of a fulfillment lifecycle  |

Any deviation from this vocabulary is considered a defect.

---

## Implemented Fulfillment Flows

### 1. QR / Online Fulfillment

#### Flow

1. Authenticated warehouse user scans fulfillment token
2. Token row is locked (`SELECT … FOR UPDATE`)
3. Token validity is verified:

   * Exists
   * Not used
   * Not expired
4. Sale items are reloaded and hashed
5. Hash is compared to token payload
6. Token is marked as used **before** stock movement
7. Fulfillment record is created
8. Fulfillment state transitions:

   * `pending → approved`
   * `approved → released`
9. Stock is decreased via `StockService` (idempotent)
10. Fulfillment state transitions:

    * `released → reconciled`
11. Any failure after creation marks fulfillment as conflicted

#### Guarantees

* Token reuse is impossible
* Parallel scans cannot succeed
* Modified sale items invalidate the token
* Stock is deducted exactly once
* Final state is deterministic and auditable

---

### 2. Offline Fulfillment Reconciliation

#### Flow

1. Offline payload is created externally
2. Payload is stored as `OfflineFulfillmentPending`
3. Supervisor approves or rejects the payload
4. Approved payload is reconciled:

   * Payload structure is validated
   * Stock is decreased per item via `StockService`
   * Lifecycle audit is written
   * State is set to `reconciled`

#### Rejection Conditions

* Payload not approved
* Already reconciled
* Already rejected
* Missing or empty payload items

---

## Offline Fulfillment Lifecycle Authority

### State Machine

* `OfflineFulfillmentStateMachine` is the **sole authority** for:

  * State transitions
  * Transition validation
  * Lifecycle audit logging

#### Allowed Transitions

* `pending → approved`
* `pending → rejected`
* `approved → reconciled`

`reconciled` and `rejected` are terminal.

No service, controller, or job may bypass this machine.

---

## Service Responsibilities (Hard Boundaries)

### `StockService`

* Central authority for stock changes
* Enforces:

  * Valid movement types
  * Idempotency
  * Warehouse-product uniqueness
* No caller may mutate stock directly

### `FulfillmentService`

* Handles QR / online fulfillment flow
* Executes **side effects only**
* Does not decide lifecycle truth

### `OfflineReconciliationService`

* Coordinates approval and reconciliation
* Enforces:

  * Single reconciliation
  * Transactional integrity
* Delegates lifecycle decisions to the state machine

---

## Audit Architecture

* Lifecycle audit events are emitted **only** by state machines
* Action: `offline_fulfillment_reconciled`
* Exactly **one audit log per lifecycle**
* Audit metadata (e.g. `warehouse_id`, `business_id`) is passed into the state machine by callers
* Duplicate lifecycle audits are forbidden

---

## Model Guards

* `OfflineFulfillmentPending` enforces a guard preventing mutation of `status`
* Guard triggers only when `status` is dirty (`isDirty('status')`)
* This prevents schema drift while avoiding false positives

---

## Testing Philosophy

This system is **test-defined**, not test-decorated.

Feature tests define invariants:

* `QrScanTest`
* `OfflineReconciliationTest`

Tests are treated as **architectural constraints**, not regression checks.

Any change that:

* Breaks determinism
* Reintroduces lifecycle ambiguity
* Allows duplicate side effects

must fail tests immediately.

---

## Current Status

* ✔ All feature tests passing
* ✔ Lifecycle determinism enforced
* ✔ Audit duplication eliminated
* ✔ Stock engine stable
* ✔ Authorization boundaries respected

---

## Design Position

This system is intentionally **defensive by default**.

It is designed to operate safely across:

* Multiple warehouses
* Offline and untrusted environments
* Concurrent requests
* Partial failures

Correctness is prioritized over convenience.
Safety is prioritized over speed.

---

### Final Note

This document is **authoritative**.

Any future change that contradicts these rules is a **design regression**, not a feature.

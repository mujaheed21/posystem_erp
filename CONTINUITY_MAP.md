# CONTINUITY MAP

## Purpose

This document defines **continuity rules** that must hold across code, database schema, tests, and operational behavior.

It exists to prevent:

* Silent contract drift
* Ambiguous ownership of state
* Hidden coupling between services
* Nondeterministic behavior that breaks tests

Any violation of this map is a **system-level defect**.

---

## Canonical Vocabulary (Enforced)

| Term        | Meaning                        | Allowed Usage                          |
| ----------- | ------------------------------ | -------------------------------------- |
| `state`     | Persisted lifecycle truth      | Database, domain logic, state machines |
| `status`    | Transport / response indicator | API responses only                     |
| Lifecycle   | Controlled state progression   | State machines only                    |
| Side effect | Non-state operation            | Services only                          |

**Rule:** `status` MUST NOT appear in persistence or domain logic.

---

## Offline Fulfillment Continuity

### Lifecycle Authority

* `OfflineFulfillmentStateMachine` is the **only component** allowed to:

  * Read lifecycle truth
  * Validate transitions
  * Mutate `state`
  * Emit lifecycle audit events

No service, controller, job, or test helper may bypass this authority.

---

### Allowed State Transitions

```
pending  → approved → reconciled
pending  → rejected
```

* `reconciled` and `rejected` are terminal
* Backward or repeated transitions are forbidden

---

### Persistence Rules

* `state` is the only persisted lifecycle column
* `status` may exist historically but must never be mutated
* Model-level guards must prevent `status` mutation
* Guards must trigger on **intentional mutation only** (`isDirty('status')`)

---

### Audit Continuity

* Lifecycle audit events are emitted **once and only once**
* Audit action: `offline_fulfillment_reconciled`
* Audit emission occurs **inside the state machine**
* Services must pass metadata, not emit audits

Duplicate lifecycle audits are forbidden.

---

### Side Effect Boundary

* `FulfillmentService::fulfillOffline()`:

  * Performs stock deduction only
  * Must not mutate lifecycle state
  * Must not emit lifecycle audit logs

* Stock mutations:

  * Must pass through `StockService`
  * Must be idempotent
  * Must be auditable

---

## QR / Online Fulfillment Continuity

### Token Guarantees

* Fulfillment tokens are:

  * Single-use
  * Row-locked during processing
  * Invalid after use or expiry

Parallel or replay execution must fail deterministically.

---

### Online Fulfillment Lifecycle

```
pending → approved → released → reconciled
```

* Transitions are forward-only
* Final states are terminal
* Partial execution results in a conflicted fulfillment

---

### Online vs Offline Separation

* Online fulfillment uses `FulfillmentStateMachine`
* Offline fulfillment uses `OfflineFulfillmentStateMachine`
* These machines are independent
* No shared lifecycle fields
* No cross-machine transitions

---

## Stock Continuity

* Stock is a ledger, not a counter
* `StockService` is the sole authority
* Each stock mutation must:

  * Create a movement record
  * Be idempotent
  * Be scoped to a business + warehouse + reference

---

## Authorization Continuity

* Authorization is explicit and enforced at boundaries
* Required permissions:

  * `warehouse.fulfill`
  * `offline.fulfillment.approve`

No implicit trust or role inference is allowed.

---

## Testing Continuity

Tests are part of the continuity contract.

* Feature tests define invariants
* Tests must assume deterministic behavior
* Any change causing:

  * Duplicate side effects
  * Ambiguous state
  * Multiple audits per lifecycle

must fail tests immediately.

---

## Change Discipline

Any change that affects:

* Lifecycle semantics
* Audit behavior
* State naming
* Stock mutation rules

MUST update:

1. `PROJECT_CONTEXT.md`
2. This `CONTINUITY_MAP.md`
3. Relevant feature tests

Failure to update all three constitutes a **continuity violation**.

---

## Final Assertion

This continuity map is **binding**.

Code that contradicts it is incorrect, even if it appears to work.

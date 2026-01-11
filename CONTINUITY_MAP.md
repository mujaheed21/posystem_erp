# CONTINUITY MAP

<<<<<<< HEAD
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
=======
This document defines **non-negotiable architectural and policy invariants** for the Secure Multi-Location POS & Warehouse Fulfillment System.

Its purpose is to prevent contradictory design decisions, policy drift, and implementation shortcuts that would undermine security, auditability, or legal defensibility.

This file is **authoritative**. Where conflicts arise, this document takes precedence over implementation convenience.

---

## 1. Core Continuity Principles

### 1.1 State Is the Source of Truth

* All business entities must have an explicit, persisted `state`.
* State transitions must occur **only through state machines**.
* Direct mutation of state outside state machines is prohibited in production logic.

**Rationale:** Prevents hidden transitions and ensures deterministic lifecycle control.

---

### 1.2 State Machines Are the Only Lifecycle Authority

* Controllers, services, jobs, and tests must not bypass state machines.
* State machines must:

  * validate transitions
  * enforce invariants
  * reject illegal paths

**Tests may set state directly only when asserting invariants, not simulating workflows.**

---

### 1.3 Overrides Are Security Events, Not Permissions

* Supervisor overrides do not grant standing authority.
* Overrides apply **only** to explicitly protected transitions.
* Overrides are evaluated at transition time, not before.

**Rationale:** Prevents privilege escalation and override reuse.

---

## 2. Supervisor Override Policy (Authoritative)

### 2.1 When Overrides Apply

An override is required **only if all conditions are met**:

* Current state is explicitly protected
* Target transition is classified as high-risk
* The entity declares `requires_override = true`

Overrides must never be implied or inferred.

---

### 2.2 Override Scope & Binding

Each override:

* Is bound to exactly one:

  * event type
  * model type
  * record ID
* Cannot be reused across entities or transitions
* Cannot be delegated

Target mismatches must result in **hard failure**.

---

### 2.3 Override Immutability

* Override records are append-only
* Updates and deletions are prohibited
* Override records must survive system restarts and migrations

**Immutability is enforced at the model level.**

---

## 3. Audit & Evidence Continuity

### 3.1 Audit Logs vs Overrides

* Audit logs record **what happened**
* Supervisor overrides record **why it was allowed**

For protected transitions:

* Both records must exist
* Missing either is a compliance failure

---

### 3.2 Evidence Readiness

All security-relevant events must be:

* Attributable to a human actor
* Timestamped
* Persisted
* Immutable

This includes:

* overrides
* reconciliations
* conflict events

---

## 4. Type & Persistence Guarantees

### 4.1 Boolean Domain Flags

* Domain flags such as `requires_override` must:

  * be persisted
  * be mass-assignable
  * be correctly type-cast

Strict comparisons are allowed and encouraged **only when casts are correct**.

---

### 4.2 Persistence Over Assumption

* State machines read persisted database truth only
* In-memory assumptions are invalid
* Tests must explicitly persist policy flags when required

---

## 5. Testing Continuity Rules

### 5.1 Test Responsibilities

* Tests must respect production invariants
* Tests may assert invariants directly
* Tests must not weaken enforcement logic for convenience

---

### 5.2 Contract vs Implementation Tests

* Critical security modules must be protected by **contract tests**
* Contract tests freeze externally observable behavior
* Internal refactors must not alter contract outcomes

---

## 6. Explicit Non-Goals

This system does **not**:

* Infer permissions from roles
* Allow silent bypass of controls
* Trade auditability for speed
* Permit post-event justification

---

## 7. Conflict Resolution Rule

If any file, test, or implementation:

* contradicts this document
* weakens an invariant
* bypasses declared policies

**The implementation must change — not this document.**

---

## 8. Summary Statement

> This Continuity Map defines the invariant rules that preserve security, accountability, and audit integrity across the system lifecycle. Any deviation constitutes an architectural violation.
>>>>>>> feature/supervisor-override-engine

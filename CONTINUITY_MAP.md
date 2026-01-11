# CONTINUITY MAP

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

# FRONTEND_FEATURE_MAP.md

**Frontend Feature Inventory & Implementation Roadmap**

> **Purpose:** This document is the **single source of truth** for *what exists, what is placeholder, and what remains to be implemented* on the frontend.
>
> It complements (but never overrides):
>
> * `CONTINUITY_MAP.md` (system law)
> * `FRONTEND_POLICY.md` (UI law)
>
> Think of this file as the **UI backlog + feature registry**, not a policy document.

---

## 1. How to Use This Document

Every frontend feature must appear here **before** implementation.

Each feature must be tagged as one of:

* **IMPLEMENTED** – fully wired to backend services
* **PLACEHOLDER** – UI shell only, no business logic
* **LOCKED** – intentionally restricted (policy / supervisor only)
* **PLANNED** – not yet visible in UI

No feature should exist in the UI without an entry here.

---

## 2. Top Bar Features

| Feature              | Status      | Notes                       |
| -------------------- | ----------- | --------------------------- |
| Business Name & Logo | IMPLEMENTED | Context driven              |
| Sidebar Collapse     | IMPLEMENTED | UI only                     |
| POS Shortcut Icon    | IMPLEMENTED | Permission + register gated |
| Today’s Profit       | IMPLEMENTED | Read-only, ledger-derived   |
| Date Display         | IMPLEMENTED | System date                 |
| Notifications        | PLACEHOLDER | Audit + system events       |
| Calendar / To-do     | PLACEHOLDER | Future productivity module  |
| Calculator           | PLACEHOLDER | Utility modal               |
| Clock-in Addon       | PLANNED     | HR module                   |
| User Profile Menu    | IMPLEMENTED | Logout, profile             |

---

## 3. Sidebar — Business User

### Dashboard

| Feature            | Status      | Notes                 |
| ------------------ | ----------- | --------------------- |
| Business Dashboard | IMPLEMENTED | KPIs only, no actions |

---

### Sell

| Feature      | Status      | Notes              |
| ------------ | ----------- | ------------------ |
| POS          | IMPLEMENTED | Sale + reservation |
| Sales List   | IMPLEMENTED | Read-only history  |
| Drafts       | PLACEHOLDER | No logic yet       |
| Quotations   | PLACEHOLDER | Future             |
| Sell Returns | IMPLEMENTED | Ledger reversal    |
| Discounts    | PLACEHOLDER | Policy-bound       |
| Import Sales | PLANNED     | High-risk          |

---

### Purchases

| Feature           | Status      | Notes                |
| ----------------- | ----------- | -------------------- |
| Add Purchase      | IMPLEMENTED | Ledger + inventory   |
| List Purchases    | IMPLEMENTED | Read-only            |
| Purchase Returns  | PLACEHOLDER | Reversal-based later |
| Supplier Payments | IMPLEMENTED | Payables lifecycle   |

---

### Products

| Feature              | Status      | Notes           |
| -------------------- | ----------- | --------------- |
| List Products        | IMPLEMENTED | Stock-aware     |
| Add Product          | IMPLEMENTED | Master data     |
| Update Price         | PLACEHOLDER | Requires policy |
| Print Labels         | PLACEHOLDER | Utility         |
| Import Products      | PLACEHOLDER | Risk controlled |
| Import Opening Stock | LOCKED      | Audited only    |
| Units                | IMPLEMENTED | Master data     |
| Categories           | IMPLEMENTED | Master data     |
| Brands               | PLACEHOLDER | Optional        |
| Warranties           | PLACEHOLDER | Optional        |

---

### Stock & Warehouse

| Feature           | Status      | Notes                             |
| ----------------- | ----------- | --------------------------------- |
| Warehouses (List) | IMPLEMENTED | Per business                      |
| Add Warehouse     | IMPLEMENTED | Business admin only               |
| Stock List        | IMPLEMENTED | Available / Reserved / In-transit |
| Stock Transfers   | IMPLEMENTED | QR-backed, per business           |
| Stock Adjustments | LOCKED      | Supervisor + ledger               |
| Low Stock Alerts  | IMPLEMENTED | Read-only                         |

---

### Fulfillment (Warehouse Operations)

| Feature                     | Status      | Notes                                      |
| --------------------------- | ----------- | ------------------------------------------ |
| Fulfillment Queue (Online)  | IMPLEMENTED | Pending → Approved → Released → Reconciled |
| Fulfillment Queue (Offline) | IMPLEMENTED | Pending → Approved → Reconciled            |
| Approve Fulfillment         | IMPLEMENTED | Permission + supervisor enforced           |
| Release Fulfillment         | IMPLEMENTED | Idempotent, stock commit                   |
| Reconcile Fulfillment       | IMPLEMENTED | Ledger + audit guarded                     |
| QR Scan Fulfillment         | IMPLEMENTED | Auth, expiry, replay protected             |
| Supervisor Overrides        | IMPLEMENTED | Explicit, audited                          |

---

### Expenses

| Feature            | Status      | Notes             |
| ------------------ | ----------- | ----------------- |
| Add Expense        | IMPLEMENTED | Approval governed |
| List Expenses      | IMPLEMENTED | Read-only         |
| Expense Categories | PLACEHOLDER | Master data       |

---

### Accounting

| Feature           | Status      | Notes       |
| ----------------- | ----------- | ----------- |
| Ledger            | IMPLEMENTED | Immutable   |
| Chart of Accounts | PLACEHOLDER | Read-only   |
| Journals          | LOCKED      | System only |

---

### Reports (Read-only)

*All reports start as PLACEHOLDER shells unless backed by tested services.*

| Report          | Status      |
| --------------- | ----------- |
| Profit & Loss   | IMPLEMENTED |
| Purchase & Sale | IMPLEMENTED |
| Stock Report    | IMPLEMENTED |
| Register Report | IMPLEMENTED |
| Activity Log    | IMPLEMENTED |
| Others          | PLACEHOLDER |

---

### Settings

| Feature              | Status      | Notes         |
| -------------------- | ----------- | ------------- |
| Business Settings    | PLACEHOLDER | Config only   |
| Business Locations   | IMPLEMENTED | Context bound |
| Roles & Permissions  | IMPLEMENTED | Spatie-backed |
| Modules              | IMPLEMENTED | SaaS enforced |
| Package Subscription | PLACEHOLDER | SaaS billing  |

---

## 4. Super Admin (SaaS)

| Feature          | Status      | Notes           |
| ---------------- | ----------- | --------------- |
| Businesses       | IMPLEMENTED | Tenant control  |
| Business Modules | IMPLEMENTED | Feature toggles |
| Plans            | PLACEHOLDER | Monetization    |
| Platform Audit   | PLACEHOLDER | Cross-tenant    |

---

## 5. Governance Rule

* No feature may move from PLACEHOLDER → IMPLEMENTED without:

  * Backend capability
  * Tests (where applicable)
  * Policy compliance (`FRONTEND_POLICY.md`)

This document must be updated **before code is written**, not after.

---

**This file is operational, not theoretical.**
It is expected to change as the system evolves.

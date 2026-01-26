# FRONTEND_FEATURE_MAP.md

## Frontend Feature Inventory & Implementation Roadmap

**Purpose**
This document is the **single source of truth** for the frontend feature landscape—what is implemented, what is skeletal, what is restricted, and what is planned.

It complements (but never overrides):

* `CONTINUITY_MAP.md` — **System Law**
* `FRONTEND_POLICY.md` — **UI Law**

This file functions as a **UI feature registry and backlog**, not a policy document.

---

## Feature Status Definitions

Every frontend feature **must be registered here before implementation** and classified as one of the following:

* **IMPLEMENTED** — Fully wired to backend services and policies
* **PLACEHOLDER** — UI shell only; no business logic
* **LOCKED** — Intentionally restricted (policy / supervisor-only)
* **PLANNED** — Not yet visible in the UI

> No feature may exist in the UI without an entry in this document.

---

## System Architecture & Technical Stack

**Status:** DEFINITIVE
**Approved:** 2026-01-24
**Target:** Strictly API-based ERP with offline-capable workflows

### 1. Core Engine

* **Runtime:** Node.js (pnpm)
* **Build Tool:** Vite (React SPA template)
* **Language:** TypeScript (Strict Mode)
* **Backend:** Laravel 11+ (Stateless REST API)

### 2. UI & Design System (UltimatePOS Aesthetic)

* **Component Library:** Ant Design (antd) v5+
* **Icons:** `@ant-design/icons`
* **Styling:** Tailwind CSS (utility-first layer)
* **Typography:** Inter / Roboto
* **Theme Configuration:**

  * Primary Color: `#1677ff` (Corporate Blue)
  * Border Radius: `4px` (Industrial / Compact)

### 3. Data & State Management

* **Server State:** TanStack Query (React Query) v5
  *Caching, background sync, terminal state hydration*
* **Client State:** Zustand
  *Registers, session-scoped UI state*
* **HTTP Client:** Axios
  *Laravel Sanctum via interceptors*

### 4. Invariant Enforcement & Logistics

* **Financial Math:** `currency.js` (ledger-safe arithmetic)
* **Security:** `crypto-js` (fulfillment token signing)
* **QR Scanning:** `html5-qrcode`
* **Offline Storage:** Dexie.js (IndexedDB wrapper)

---

## Installation Guide

Run from the project root:

```bash
pnpm add antd @ant-design/icons @tanstack/react-query axios dayjs currency.js crypto-js qrcode.react html5-qrcode dexie
```

---

## Top Bar Features

| Feature              | Status      | Notes                       |
| -------------------- | ----------- | --------------------------- |
| Business Name & Logo | IMPLEMENTED | Context-driven              |
| Sidebar Collapse     | IMPLEMENTED | UI only                     |
| POS Shortcut Icon    | IMPLEMENTED | Permission + register gated |
| Today’s Profit       | IMPLEMENTED | Read-only, ledger-derived   |
| Date Display         | IMPLEMENTED | System date                 |
| Notifications        | PLACEHOLDER | Audit + system events       |
| Calendar / To-do     | PLACEHOLDER | Productivity module         |
| Calculator           | PLACEHOLDER | Utility modal               |
| Clock-in Addon       | PLANNED     | HR module                   |
| User Profile Menu    | IMPLEMENTED | Profile + logout            |

---

## Sidebar — Business User

### Dashboard

| Feature            | Status      | Notes                 |
| ------------------ | ----------- | --------------------- |
| Business Dashboard | IMPLEMENTED | KPIs only, no actions |

---

### Sell

| Feature      | Status      | Notes               |
| ------------ | ----------- | ------------------- |
| POS          | IMPLEMENTED | Sale + reservation  |
| Sales List   | IMPLEMENTED | Read-only history   |
| Drafts       | PLACEHOLDER | No logic yet        |
| Quotations   | PLACEHOLDER | Future              |
| Sell Returns | IMPLEMENTED | Ledger reversal     |
| Discounts    | PLACEHOLDER | Policy-bound        |
| Import Sales | PLANNED     | High-risk operation |

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
| Update Price         | PLACEHOLDER | Policy-gated    |
| Print Labels         | PLACEHOLDER | Utility         |
| Import Products      | PLACEHOLDER | Risk-controlled |
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
| Stock Adjustments | LOCKED      | Supervisor + ledger enforced      |
| Low Stock Alerts  | IMPLEMENTED | Read-only                         |

---

### Fulfillment (Warehouse Operations)

| Feature                   | Status      | Notes                                      |
| ------------------------- | ----------- | ------------------------------------------ |
| Online Fulfillment Queue  | IMPLEMENTED | Pending → Approved → Released → Reconciled |
| Offline Fulfillment Queue | IMPLEMENTED | Pending → Approved → Reconciled            |
| Approve Fulfillment       | IMPLEMENTED | Supervisor enforced                        |
| Release Fulfillment       | IMPLEMENTED | Idempotent, stock commit                   |
| Reconcile Fulfillment     | IMPLEMENTED | Ledger + audit guarded                     |
| QR Scan Fulfillment       | IMPLEMENTED | Auth, expiry, replay-safe                  |
| Supervisor Overrides      | IMPLEMENTED | Explicit, audited                          |

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
| Journals          | LOCKED      | System-only |

---

### Reports (Read-only)

*All reports begin as PLACEHOLDER shells unless backed by tested services.*

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
| Business Locations   | IMPLEMENTED | Context-bound |
| Roles & Permissions  | IMPLEMENTED | Spatie-backed |
| Modules              | IMPLEMENTED | SaaS enforced |
| Package Subscription | PLACEHOLDER | Billing       |

---

## Super Admin (SaaS)

| Feature          | Status      | Notes           |
| ---------------- | ----------- | --------------- |
| Businesses       | IMPLEMENTED | Tenant control  |
| Business Modules | IMPLEMENTED | Feature toggles |
| Plans            | PLACEHOLDER | Monetization    |
| Platform Audit   | PLACEHOLDER | Cross-tenant    |

---

## Governance Rule

No feature may transition from **PLACEHOLDER → IMPLEMENTED** without:

* Backend capability
* Tests (where applicable)
* Policy compliance (`FRONTEND_POLICY.md`)

> This document must be updated **before code is written**, not after.

---

**This file is operational, not theoretical.**
It is expected to evolve alongside the system.

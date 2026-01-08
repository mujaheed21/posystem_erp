Below is the authoritative continuity map for this project.

If these classes/files are referenced, named, and wired correctly, the system will not break as it grows.

This is written as a lead engineer checklist, not a tutorial.

---

## 1. CONFIGURATION & ENV (FOUNDATIONAL)

These must never drift.

.env

• DB_*
• OFFLINE_QR_ACTIVE_KID
• SANCTUM_STATEFUL_DOMAINS (if frontend added)

---

config/offline_qr.php

• Must exist
• Must expose active_kid

Breakage risk:
If OFFLINE_QR_ACTIVE_KID mismatches key files → QR signing fails silently.

---

## 2. CRYPTOGRAPHY (CRITICAL, HIGH-RISK)

Files that must stay consistent

• app/Services/OfflineQrSigner.php
• app/Services/OfflineQrVerifier.php
• storage/keys/{kid} (64 bytes, binary)
• storage/keys/{kid}.pub (32 bytes, binary)

Hard rules

• ❌ Never regenerate keys without rotation logic
• ❌ Never change payload canonicalization order
• ❌ Never treat keys as text

Breakage risk:
Any change here invalidates all offline QR codes.

---

## 3. FULFILLMENT CORE (BUSINESS-CRITICAL — COMPLETED)

Online fulfillment (stable)

• app/Http/Controllers/Api/FulfillmentController.php
• app/Services/FulfillmentService.php
• app/Services/FulfillmentTokenService.php
• app/Models/FulfillmentToken.php

Guarantees enforced

• Single-use tokens
• Token expiry
• Sale item hash integrity
• Warehouse-bound fulfillment
• Row-level locking
• Stock decrease via StockService only

Offline fulfillment (prepared, not expanded)

• app/Http/Controllers/Api/OfflineFulfillmentController.php
• offline_fulfillment_pendings table
• OfflineQrVerifier

Breakage risk:
Mixing offline and online logic → double fulfillment or stock corruption.

---

## 4. AUTH & ACCESS CONTROL (SYSTEM INTEGRITY — STABLE)

Auth model

• app/Models/User.php
• Must use:
• HasApiTokens
• HasRoles

• Sanctum middleware must remain enabled

Permissions (Spatie)

• Permissions referenced in routes must exist
• sale.create
• warehouse.fulfill

Middleware

• permission:*

Custom middleware

• app/Http/Middleware/EnsureWarehouseAccess.php
• Registered in bootstrap/app.php

Special rule

• QR fulfillment route bypasses warehouse access middleware internally

Breakage risk:
Changing middleware behavior breaks QR fulfillment tests.

---

## 5. ROUTING (FRAGILE IF TOUCHED CARELESSLY)

Routes that must remain stable

• /api/sales
• /api/fulfillments/scan
• /api/fulfillments/offline-scan

Files

• routes/web.php (Laravel 11)

Middleware ordering (do not reorder)

1. auth:sanctum
2. permission:*
3. warehouse.access
4. throttle

Breakage risk:
Wrong order → valid users blocked or unauthorized access allowed.

---

## 6. DATABASE SCHEMA (STRUCTURAL DEPENDENCIES — VERIFIED)

Tables that must not lose columns

• sales
• sale_number
• warehouse_id
• business_location_id

• sale_items
• fulfillment_tokens
• offline_fulfillment_pendings
• warehouse_stock
• stock_movements
• warehouse_fulfillments
• audit_logs

Foreign keys that must remain

• sale_items.product_id → products.id
• sales.warehouse_id → warehouses.id
• warehouse_stock.warehouse_id → warehouses.id

Breakage risk:
Removing “unused” columns breaks fulfillment and tests.

---

## 7. STOCK ENGINE (AUTHORITATIVE — COMPLETED)

Files

• app/Services/StockService.php

Rules enforced

• Stock increase vs decrease strictly separated
• No negative stock allowed
• Row locking on stock mutation
• All movements journaled

Breakage risk:
Bypassing StockService corrupts inventory state.

---

## 8. TEST INFRASTRUCTURE (GUARD RAILS — GREEN)

Core test files

• tests/Feature/Fulfillment/QrScanTest.php
• tests/Helpers/FulfillmentTestHelper.php

Status

• All tests passing
• Tests encode security + business invariants

Breakage risk:
Changing behavior without updating tests creates false confidence.

---

## 9. AUDIT & NON-REPUDIATION (LEGAL DEFENSIBILITY)

Must remain referenced

• audit_logs table

Audit writes in

• Fulfillment approval
• Stock decrease
• Offline pending creation
• Future reconciliation

Breakage risk:
Removing audit destroys traceability and legal defensibility.

---

## 10. FILES YOU SHOULD NEVER “CLEAN UP” BLINDLY

❌ bootstrap/app.php (middleware registration)
❌ config/offline_qr.php
❌ FulfillmentTokenService
❌ FulfillmentService
❌ StockService
❌ EnsureWarehouseAccess
❌ OfflineQrSigner / Verifier

These are structural load-bearing walls.

---

## 11. CURRENT SAFE EXPANSION ZONE

Next modules must mirror fulfillment patterns:

• Purchase Order
• Purchase Receipt (Goods Receiving)
• Stock Increase via StockService

Any deviation must be justified.

---

## 12. SINGLE SENTENCE RULE (KEEP THIS)

If a file enforces identity, authorization, cryptography, stock mutation, or irreversibility — it is continuity-critical.

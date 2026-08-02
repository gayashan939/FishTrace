# Retailer workflow

Incoming labels, receipts, inventory, sales, and retailer alerts use authorized Form Requests with pagination capped at 100 and organization-scoped reads from the typed retail query service. Receipt, label, inventory, movement, sale, and alert Resources explicitly whitelist the Flutter contract. Package public tokens and persisted sale request fingerprints are never returned; the client reference remains available for offline idempotency. Client-supplied label and lot IDs are resolved inside locked use-case Actions rather than controllers.

Retail inventory is organization-scoped and starts from a processor-issued `package_label`. A label can be received only once. Intake locks the label, verifies that its full package count was received, validates the active retail location, calculates received weight from the trusted label weight, creates the receipt and inventory lot, records the opening stock movement, and appends public retailer custody events in one transaction.

## Inventory states

- `IN_STOCK`: packages are available for sale.
- `RESERVED`: at least one package is reserved; only the remaining available count can be sold.
- `SOLD_OUT`: no available or reserved packages remain.
- `RECALLED`: the remaining lot is quarantined and cannot be sold.
- `EXPIRED`: the remaining lot is unavailable and cannot be sold.

Every receipt, reservation, release, sale, recall, and expiry creates an immutable stock-movement snapshot. The invariant `total_packages = available_packages + reserved_packages + sold_packages` is preserved for active lots. Recall and expiry retain the counters for audit while their terminal status prevents sale.

`POST /api/v1/retailer/stock-adjustments` records a reasoned `ADD` or `REMOVE` correction under an inventory-lot row lock. Added packages increase total and available counts; removals can consume only available packages and reduce both total and available counts. Reserved, sold, recalled, and expired quantities cannot be silently removed, and every correction creates an immutable `ADJUSTED_IN` or `ADJUSTED_OUT` movement.

## Sales

`POST /api/v1/retail/sales` accepts a retailer-generated UUID `client_reference`, a location, and distinct inventory items. The organization row and inventory lots are locked in deterministic order. The action rejects cross-location inventory, terminal or expired lots, and quantities beyond available stock. A unique organization/client-reference constraint prevents duplicate sales, while a persisted canonical request fingerprint ensures the key can be replayed only with the same location, lot quantities, and normalized prices. A changed-payload replay returns HTTP 409 without changing inventory.

Each completed sale creates line items, decrements available stock, increments sold stock, records stock movements, and appends public `RETAIL_SALE` events. A batch moves to `SOLD` after no received inventory for that batch remains available or reserved.

## API surface

- `GET /api/v1/retail/dashboard`
- `GET /api/v1/retail/incoming-labels`
- `GET /api/v1/retail/receipts`
- `POST /api/v1/retail/package-labels/{label}/receive`
- `GET /api/v1/retail/inventory-lots`
- `GET /api/v1/retail/inventory-lots/{lot}`
- `POST /api/v1/retail/inventory-lots/{lot}/reserve`
- `POST /api/v1/retail/inventory-lots/{lot}/release`
- `POST /api/v1/retail/inventory-lots/{lot}/recall`
- `POST /api/v1/retail/inventory-lots/{lot}/expire`
- `GET|POST /api/v1/retail/sales`
- `GET /api/v1/retail/sales/{sale}`

The original Flutter contract is also available without redirects at `/api/v1/retailer/dashboard`, `GET|POST /api/v1/retailer/receipts`, `GET /api/v1/retailer/receipts/{receipt}`, `GET /api/v1/retailer/inventory[/{lot}]`, and `GET|POST /api/v1/retailer/sales[/{sale}]`. The route-bound `/retail/package-labels/{label}/receive` remains the preferred scan flow. Duplicate receipt attempts are workflow conflicts (`409`), not authorization failures.

Retail alert visibility is derived from owned inventory rather than the transporter organization. `GET /api/v1/retailer/alerts` returns only batch-linked alerts affecting the retailer's lots. Quarantine marks every saleable affected lot as recalled under locks and acknowledges the alert; resolution is a separate locked and audited transition. Retail summary, inventory, and sales reports are exposed at `/api/v1/retailer/reports/*` through the shared organization-scoped report engine.

Package tokens remain hidden from authenticated API serialization. Public trace access continues through the privacy-filtered QR endpoint.

# Administrator retail operations

The Retail Operations console is available under `/admin/retail/*` and is restricted to administrators. It provides cross-organization, read-only visibility from processor-issued package receipt through inventory movements, sales, expiry, and recall.

## Operational surfaces

- Retailer pages link retailer identities and organizations to locations, the newest 100 receipts, and the newest 100 sales.
- Location pages summarize receipt, inventory, and sales counts and show the newest 100 lots and sales.
- Custody receipts connect package labels and source batches to retailer, location, receiving actor, package/weight reconciliation, condition temperature, and the resulting inventory lot.
- Inventory pages expose total, available, reserved, and sold package reconciliation plus the newest 250 immutable movement snapshots and linked sale lines.
- The stock ledger provides filterable, actor-attributed movement history for received, reserved, released, sold, recalled, and expired stock.
- Sales pages show receipt number, seller, location, totals, and traceable lot/label/batch line items.
- Expiry and recall monitoring isolates recalled, expired, and within-seven-days inventory for operational attention.

Directories use validated server-side filters and bounded pagination. Detail collections are capped where operational history can grow without bound.

## Privacy and authority boundaries

The console reads permanent MySQL/MariaDB records and does not mutate retail workflows. Retailer API policies remain organization-scoped while cross-organization console access is administrator-only.

Package public tokens, sale client references, private integration fields, and authentication material are excluded from pages and exports. Receipt numbers and operational label codes remain visible for reconciliation.

## Exports and audit

Inventory and sales CSV exports apply the same validated filters as their directories, are capped at 10,000 rows, and use safe download headers. Downloads create `RETAIL_INVENTORY_EXPORTED` or `RETAIL_SALES_EXPORTED` audit records with actor, filters, row count, and request correlation.

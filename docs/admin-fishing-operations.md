# Administrator fishing operations

The Fishing Operations console is available under `/admin/fishing/*` and is restricted to administrators. It exposes four linked operational directories: fishers, boats, fishing trips, and catches. Supply-chain users continue using their organization-scoped API routes; an authenticated fisher cannot enter these cross-organization administrator pages.

## Authoritative fisher profiles

FishTrace has no separate fisher-profile table. An operational fisher profile is derived from the `users` record, `FISHER` role, organization memberships, owned boats, fishing trips, and catch aggregates. This avoids duplicating identity or membership state. Fisher and boat detail histories show at most the newest 100 records while database aggregates retain complete totals.

## Trip and catch reconciliation

Trip details show organization, fisher, vessel, capacity, catch area, landing site, voyage timestamps, catches, species, gear, quantities, and weights. Catch details link every batch allocation through the `batch_catches` ledger.

For each catch, the console independently calculates:

- caught weight;
- the denormalized `catch_records.allocated_weight_kg` value;
- the sum of `batch_catches.allocated_weight_kg`;
- unallocated and overallocated weight; and
- whether the stored total disagrees with the pivot ledger.

Allocation filters support `UNALLOCATED`, `PARTIAL`, `FULL`, `OVERALLOCATED`, and `MISMATCH`. The pivot ledger is treated as authoritative for reconciliation. Over-allocation and stored-ledger mismatches are displayed prominently and are never silently corrected by this read-only console.

## Search and exports

Directories support server-side search, organization/fisher/boat/species/status/allocation/date filters, whitelisted sorting, and pagination capped at 100 rows. Trip and catch CSV exports apply the same validated filters, are capped at 10,000 rows, set safe download headers, and record `FISHING_TRIPS_EXPORTED` or `CATCH_RECORDS_EXPORTED` audit events with filters and row counts.

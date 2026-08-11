# FishTrace implementation checklist

Audit date: 2026-08-01

The master-prompt catalogue is implemented as 40 dedicated screens: 8 shared,
8 fisher, 8 processor, 9 transporter, and 7 retailer. `Pass` means the screen
has its own reference-driven composition, explicit route, controller-backed
state, mock data, applicable validation/states, and a verified 390 × 844
capture. A dash means the criterion is not applicable to that screen.

| # | Screen | UI | Route | State/mock | Validation | 390 × 844 |
|---:|---|:---:|:---:|:---:|:---:|:---:|
| 1 | Splash | Pass | Pass | Pass | — | Pass |
| 2 | Onboarding | Pass | Pass | Pass | Pass | Pass |
| 3 | Login | Pass | Pass | Pass | Pass | Pass |
| 4 | Password recovery | Pass | Pass | Pass | Pass | Pass |
| 5 | Notifications | Pass | Pass | Pass | — | Pass |
| 6 | Profile and settings | Pass | Pass | Pass | Pass | Pass |
| 7 | Help and support | Pass | Pass | Pass | Pass | Pass |
| 8 | Offline sync status | Pass | Pass | Pass | Pass | Pass |
| 9 | Fisher dashboard | Pass | Pass | Pass | — | Pass |
| 10 | Boat management | Pass | Pass | Pass | Pass | Pass |
| 11 | Start fishing trip | Pass | Pass | Pass | Pass | Pass |
| 12 | Active trip details | Pass | Pass | Pass | Pass | Pass |
| 13 | Add catch and location | Pass | Pass | Pass | Pass | Pass |
| 14 | Catch history and details | Pass | Pass | Pass | Pass | Pass |
| 15 | Create batch | Pass | Pass | Pass | Pass | Pass |
| 16 | Batch details and QR | Pass | Pass | Pass | Pass | Pass |
| 17 | Processor dashboard | Pass | Pass | Pass | — | Pass |
| 18 | Scan batch | Pass | Pass | Pass | Pass | Pass |
| 19 | Batch intake details | Pass | Pass | Pass | Pass | Pass |
| 20 | Processing workflow | Pass | Pass | Pass | Pass | Pass |
| 21 | Quality inspection | Pass | Pass | Pass | Pass | Pass |
| 22 | Split and pack batch | Pass | Pass | Pass | Pass | Pass |
| 23 | Processed batch details | Pass | Pass | Pass | Pass | Pass |
| 24 | Processing history and reports | Pass | Pass | Pass | Pass | Pass |
| 25 | Transporter dashboard | Pass | Pass | Pass | — | Pass |
| 26 | Trips list | Pass | Pass | Pass | Pass | Pass |
| 27 | Trip details | Pass | Pass | Pass | Pass | Pass |
| 28 | Scan and add batch | Pass | Pass | Pass | Pass | Pass |
| 29 | IoT device assignment | Pass | Pass | Pass | Pass | Pass |
| 30 | Vehicle management | Pass | Pass | Pass | Pass | Pass |
| 31 | Pre-trip checklist | Pass | Pass | Pass | Pass | Pass |
| 32 | Live monitoring | Pass | Pass | Pass | Pass | Pass |
| 33 | Delivery confirmation | Pass | Pass | Pass | Pass | Pass |
| 34 | Retailer dashboard | Pass | Pass | Pass | — | Pass |
| 35 | Receive batch | Pass | Pass | Pass | Pass | Pass |
| 36 | Received batch details | Pass | Pass | Pass | Pass | Pass |
| 37 | Inventory and product details | Pass | Pass | Pass | Pass | Pass |
| 38 | Stock and sales update | Pass | Pass | Pass | Pass | Pass |
| 39 | Retail alerts | Pass | Pass | Pass | Pass | Pass |
| 40 | Sales history and reports | Pass | Pass | Pass | Pass | Pass |

## Acceptance gates

- [x] Feature-first folders and repository/controller boundaries
- [x] Shared design tokens and component library
- [x] All 39 master-prompt components implemented, exported and used
- [x] Explicit typed paths; no generic workflow fallback
- [x] Authentication and cross-role route guards
- [x] Role-specific bottom navigation
- [x] Typed Drift domain schema, migrations, offline drafts and local fallback
- [x] Drift-backed offline queue, controlled backoff, idempotency and reconnect sync
- [x] Mock mode and complete Dio feature repositories in API mode
- [x] Secure tokens, 401 refresh/replay and structured network errors/logging
- [x] Laravel REST/Firebase RTDB sensor abstraction, mock warnings and local notifications
- [x] QR/manual scan, GPS, media, signature and permission/error states
- [x] Secure API token persistence and bearer interceptor
- [x] No TODO/FIXME or empty tap/button callbacks
- [x] Analyzer, full tests, mock build and API build
- [x] Forty committed 390 × 844 captures

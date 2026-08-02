# Implementation checklist

Status date: 2026-08-02. Checked items are implemented and covered by the current executable milestone; unchecked items are not claimed complete.

| Module | Migration/model | Policy/request | Action/API | UI/integration | Tests/docs |
|---|---:|---:|---:|---:|---:|
| Foundation and request IDs | ✓ | n/a | ✓ | ✓ | ✓ |
| Roles, organizations, Sanctum login/logout | ✓ | ✓ | ✓ | admin login | ✓ |
| Password OTP reset | ✓ | dedicated requests | transactional request/verify/complete actions | enumeration-safe mail delivery | ✓ |
| Flutter Firebase session | user UID | auth | ✓ | mock/admin token | ✓ |
| Boats | ✓ | dedicated create/update/delete requests + ownership policy | single-purpose create/update/delete actions + explicit resource | bounded scoped API | ✓ |
| Fishing reference data | ✓ + lifecycle indexes | three admin policies + six dedicated requests + Fisher lookup authorization | transactional audited create/update/deactivate | searchable/sortable admin registries + active mobile lookup API | ✓ |
| Fishing trips | ✓ | dedicated create/update/transition/directory requests + ownership policy | transactional draft create/update + guarded start/complete/cancel + typed query service/resource | scoped dashboard + CRUD API | ✓ |
| Catches/offline ID | ✓ | dedicated create/update/delete/directory requests + ownership policy | idempotent create + allocation-safe update/delete + explicit resource | bounded scoped API | ✓ |
| Batches/QR/timeline/documents | ✓ | batch + file policies and dedicated directory/upload requests | create/read/QR query service + explicit batch/event resources + route-bound private documents | public portal + mobile document descriptors | ✓ |
| Transport trips | ✓ + checklist/incident/delivery schema | route-aware dedicated mutation/directory requests + scoped policy | row-locked lifecycle + typed trip/vehicle/telemetry query service + explicit resources | dashboard + bounded directories/summaries/live access | ✓ |
| IoT provisioning | ✓ | device policy + dedicated lifecycle/telemetry requests | audited create/update + row-locked activate/deactivate/provision/rotate/disable | one-time credentials + privacy-whitelisted health/readings/latest/sync status | ✓ |
| Firebase assignment mirror | ✓ | transport policy | active/ended synchronization + reconciliation command | assignment/member/live cleanup | ✓ |
| Telemetry sync/cleanup/simulator | ✓ | assignment + date/period/alert-filter validation | idempotent import + bounded history and summaries | commands + reading freshness/device/alert context | ✓ |
| Admin dashboard/sensor history | ✓ | admin guard | bounded cross-module metrics service | consolidated KPI/risk/queue dashboard + telemetry links | ✓ |
| Consumer portal | ✓ | public whitelist | ✓ | ✓ | ✓ |
| Processor intake/processing/inspection/split | ✓ | route-aware mutation/directory requests + scoped policies | locked workflows + typed record/inspection/label/child/prediction query service + explicit resources | bounded mobile directories + read-only printable labels | ✓ |
| Cold-chain alert rules | ✓ + scoped configuration key | admin rule policy + dedicated update/acknowledgement requests | configurable temperature/battery/GPS/door/offline evaluation + audited acknowledgement | settings page + API alerts | ✓ |
| Retail/inventory/sales | ✓ + sale fingerprint | route-aware mutation/directory/filter requests and policies | locked workflows + typed query service + explicit receipt/inventory/sale/alert resources | bounded `/retailer` contract + route-bound scan API + scoped reports | ✓ |
| AI prediction drivers/jobs | ✓ | batch policy + dedicated request + whitelist resource | validated mock/http + batch-unique retry-safe automatic/manual queueing | decision-support API + trace milestone + high-risk/failure notifications | ✓ |
| Blockchain anchors/verification | ✓ | milestone allowlist + internal unique jobs | locked hash reservation + canonical hashing + bounded polling/failure recovery | evidence-derived public status + persisted verification history | ✓ |
| File API/notifications/reports | ✓ | bounded authorized directory requests | typed document/notification/export services + explicit privacy resources | private files + sanitized notifications + CSV/print/queued exports | ✓ |
| Immutable audit logging/search | ✓ | dedicated bounded filter request + policy | observers + typed query/export service + explicit resource | API + admin console + CSV | ✓ |
| Administrator access management | ✓ | dedicated directory/mutation requests + policies | transactional actions + typed user/organization/role directory service | explicit-resource API + functional admin pages | ✓ |
| Administrator batch operations | existing schema | admin + batch policy | cross-stage query + bounded audited CSV | registry + traceability detail + QR | ✓ |
| Administrator fishing operations | existing schema | admin + domain policies | fisher/boat/trip/catch queries + allocation reconciliation + audited CSV | four registries + four details | ✓ |
| Administrator transport/IoT operations | existing schema | explicit vehicle/device/reading/alert/trip policies | cross-stage queries + privacy-safe audited CSV | transporter/vehicle/trip/device/telemetry/alert/sync pages | ✓ |
| Administrator processor operations | existing schema | explicit profile/intake/record/step/inspection/label policies | cross-stage queries + privacy-safe audited CSV | facility/intake/processing/inspection/label pages | ✓ |
| Administrator retail operations | existing schema | explicit location/receipt/inventory/movement/sale policies | custody/stock/sale/risk queries + privacy-safe audited CSV | retailer/location/receipt/inventory/movement/sale/risk pages | ✓ |
| Administrator compliance/reporting | existing schema | explicit file/report/AI/blockchain + domain policies | incident/recall/evidence/notification/report/AI/blockchain queries + audited exports/downloads | seven compliance workspaces + details | ✓ |
| Administrator UX/accessibility hardening | n/a | admin shell | reusable status and progressive enhancement layer | mobile/active navigation, keyboard tables, control names, focus, reduced motion, filter reset | ✓ |
| System/profile/security settings | ✓ + typed setting store | dedicated system/profile/password/session requests + setting policy | cached runtime settings + transactional self-account security actions | system, profile, security, and session pages | ✓ |
| Full administrator operations console | existing schema | explicit admin + domain policies | bounded read models and audited exports/downloads | dashboard + batch/fishing/transport/processor/retail/compliance/access/audit pages | ✓ |
| Release readiness and integration harnesses | existing schema + isolated MySQL schema | Firebase client-rule deny/allow matrix | redacted strict/non-strict deployment checker | Firebase emulators + two-process sale contention | automated; MySQL environment required |
| Security/shared-hosting hardening | sync-failure uniqueness | authenticated limiter + upload/file policies | sanitized retry failures + transactional deletion + safe queue timing | secure-cookie/mail/automation diagnostics + cross-server scheduler mutexes | ✓ |

Quality gate currently exercised: fresh migrations and seed, 161 passing PHPUnit tests / 1,654 assertions, 280 application routes, 5 passing Firebase Emulator rules tests, PHPStan level 5 with zero errors, Pint, Composer audit, npm high-severity audit, scheduler discovery, Blade compilation, Vite production build, and non-strict local release diagnostics. The MySQL harness is implemented and safely refuses any schema other than `fishtrace_test`; execution still requires a MySQL/MariaDB server. Remaining environment-dependent release acceptance is tracked in `docs/release-acceptance.md`.

# Contract fix status

| Module | Database | Laravel request/resource | Flutter DTO/mapper | Mock/offline | Tests | Status |
|---|---|---|---|---|---|---|
| Authentication/common envelope | Existing | Notification list/read, authenticated profile update, and private file descriptor/download contracts FIXED | Typed notification, user, organization, file-asset, and device-settings models ADDED | Mock/API common domains aligned; settings use one shared local repository | Focused Laravel/Flutter contract tests added; static checks passed | FIXED |
| Boats | FIXED | FIXED | FIXED | FIXED (local persistence) | Focused Laravel + Flutter fixtures added | FIXED_FOR_NEW_WRITES |
| Fishing trips/catches/batches/QR/timeline | FIXED | FIXED | FIXED | FIXED | Focused fixtures and feature tests added | FIXED_FOR_NEW_WRITES |
| Processor | Assignment fields ADDED | Record, ordered steps, measurements, and quality-grade boundary FIXED | Dedicated record/step request DTOs ADDED | Ordered record/start/complete sync FIXED | Laravel workflow + Flutter serialization coverage | FIXED_FOR_PROCESSING_WORKFLOW |
| Transport/IoT | Vehicle mobile fields ADDED | Vehicle/checklist/delivery, telemetry reads, alert acknowledgement, and incident writes FIXED | Dedicated request DTOs, response mappers, and nullable telemetry mapper ADDED | Live monitor and alert/incident writes use matching mock/API domain contracts | Scoped Flutter analysis passed; focused sync coverage added | PARTIAL |
| Retail | Inventory display fields ADDED | Receipt serialization, package-count sales, report date-filter reads, and alert resolution/quarantine writes FIXED | Dedicated receipt/report/alert DTOs and typed controllers ADDED | Mock/API alerts and reports expose matching domain entities | Scoped Flutter Retail analysis passed; resolve-payload coverage added | PARTIAL |
| Files/AI/blockchain | Existing | File upload/descriptor/download and backend-only AI result/input contracts FIXED; blockchain under final audit | Typed file-asset DTO ADDED; no Flutter AI workflow exists | File upload lifecycle aligned; no speculative AI mock UI added | File tests plus AI normalizer/input/resource coverage added | PARTIAL |

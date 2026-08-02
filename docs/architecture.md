# Architecture

FishTrace is a modular Laravel monolith designed for shared hosting. MySQL/MariaDB owns identity, workflow, traceability, telemetry history, and audit state. Firebase RTDB is limited to assignment mirrors, trip membership, latest live readings, and short-lived unsynchronized telemetry.

HTTP controllers validate/authorize/delegate. Actions execute transactional use cases; services implement reusable algorithms and external adapters. Eloquent policies combine role and organization ownership. API responses carry a request ID. Integration drivers are selected by environment and mock drivers require no external service.

The first milestone flow is: Sanctum login → Firebase custom token → fisher trip/catch/batch → random QR token → transport/device assignment → Firebase mirror → ESP32 simulator → bounded import → MySQL sensor history → admin and public trace views.

External AI and blockchain services remain isolated behind HTTP/mock drivers in later implementation phases; they must never run as mandatory shared-host daemons. Database queues and cron-triggered short workers are the production execution model.

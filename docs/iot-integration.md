# IoT integration

Payload schema version 1 includes device/trip IDs, assigned batch IDs, product and air temperatures, humidity, GPS, speed, battery, signal, door state, and a millisecond UTC `recordedAt`. Each immutable message UUID is both the Firebase key and MySQL idempotency key.

Simulate data with `php artisan fishtrace:iot-simulate --trip={uuid} --scenario=normal --duration=60 --interval=10 --firebase`. Use `--mysql` to pass data through the production importer directly. Scenarios are normal, warning, critical, and offline; simulation is deterministic.

Administrator device management uses `/api/v1/iot/devices`. Create and update operations are transactional and audited. Provisioning is allowed once for an active unprovisioned device; later credential changes must use `firebase-rotate`. Provision, rotate, disable, activate, and deactivate lock the current device row before enforcing assignment and credential-state invariants. Invalid state changes return `409`. Generated Firebase passwords are returned only by successful provision/rotation calls and are never persisted by FishTrace or exposed by later reads.

Authorized transporters can read trip telemetry through `/transport-trips/{id}/sensor-readings`, `/sensor-readings/latest`, `/sensor-summary`, and `/live-access`. Device-level equivalents are available beneath `/iot/devices/{id}`. History accepts validated `date_from`, `date_to`, `page`, and `per_page` parameters; `per_page` is limited to 100. Summary `period` is `hour` or `day`. Alert history accepts `status`, `severity`, `page`, and `per_page` from fixed allowlists.

Telemetry API responses use an explicit whitelist: message/device/trip identifiers, temperatures, humidity, GPS, speed, battery, signal, door state, recorded/imported times, reading age, and device status. Trip latest state also includes `active_alert_count`. The stored `raw_payload` is never serialized by history, latest, summary, health, or live-access responses.

# Database schema

Business entities use UUID primary keys; roles and Sanctum tokens use internal integer keys. Organization IDs are copied onto organization-owned records for explicit policy scoping. Weights use `DECIMAL(12,3)`, telemetry values use bounded decimals, GPS uses `DECIMAL(10,7)`, and all event times are UTC timestamps.

The milestone schema groups identity (`users`, `roles`, `organizations`), fisher operations (`boats`, `fishing_trips`, `catch_records`), traceability (`fish_batches`, `batch_catches`, `qr_codes`, `traceability_events`), transport (`vehicles`, `transport_trips`, `transport_batches`, `device_assignments`, `pre_trip_checklists`, `checklist_items`, `transport_incidents`, `delivery_confirmations`), and telemetry (`iot_devices`, `sensor_readings`, sync cursors/failures, alerts). Unique constraints protect emails, public QR tokens, device identities, batch codes, one checklist and delivery confirmation per trip, checklist item keys, and immutable telemetry message IDs.

Fishing reference registries (`fish_species`, `fishing_gear_types`, and `landing_sites`) use active lifecycle flags rather than destructive deletion. Historical catches, batches, and trips retain their references, while validation prevents inactive values from being selected for new operational records.

Run `php artisan migrate:fresh --seed` only in disposable environments. Production uses `php artisan migrate --force` after a backup.

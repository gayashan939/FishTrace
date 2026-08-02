# FishTrace Engineering Contract

## Architecture

- FishTrace is a modular Laravel application. MySQL/MariaDB is the permanent business source of truth; Firebase Realtime Database is only the live-state, authorization-mirror, and temporary telemetry layer.
- Keep domain workflows in single-purpose Actions and integrations/cross-cutting behavior in Services behind Contracts. Controllers authorize, validate through Form Requests, delegate, and return Resources or views.
- Do not put business logic in controllers, routes, Blade templates, model accessors, observers, or middleware. Use database transactions for multi-record state transitions.
- Device and trip assignment changes originate in Laravel and are mirrored to Firebase asynchronously. Never accept Firebase as authoritative for assignments or operational records.

## Folders and Naming

- API controllers live under `app/Http/Controllers/Api/V1`; web controllers under `Admin` or `Consumer`.
- Form Requests and API Resources mirror their module beneath `app/Http/Requests` and `app/Http/Resources`.
- Actions use imperative names such as `CreateFishingTrip`; services use domain/integration names such as `TelemetryImporter`.
- Enums use singular nouns and string-backed uppercase values. Jobs use imperative names. Policies match protected model names.
- Domain-specific types may live in `app/Domain/<Module>`; Eloquent models remain in `app/Models`.

## Controllers, Requests, Resources, and Policies

- Controllers must not contain inline validation or complex queries. No empty methods, fake actions, placeholder redirects, or TODO-only implementations.
- Every mutating endpoint uses a Form Request with authorization and explicit validation rules. Normalize only transport-level input in `prepareForValidation`.
- API Resources own public serialization and must not expose secrets, internal paths, password hashes, raw private GPS coordinates, or unapproved fields.
- Every protected resource requires a registered Policy. Policies enforce both role capability and organization ownership; administrators are not implicitly exempt from organization/privacy rules unless explicitly documented.

## Actions and Services

- Actions perform one business use case and expose a typed public method. Services encapsulate reusable algorithms or external systems.
- External Firebase, AI, and blockchain access must use Contracts with configured mock and production drivers. Controllers must never call Firebase Admin directly.
- External operations are timeout-bounded, observable, retryable when safe, and isolated so failure cannot corrupt the committed business transaction.
- No duplicate business logic, no fake success responses, and no implementation whose only effect is a TODO or exception.

## Database and Migrations

- Every schema change requires a migration. Use UUID primary keys for business entities, explicit foreign keys and indexes, decimal columns for weights/money, and UTC timestamps.
- Migrations must work on MySQL 8 and compatible MariaDB. Avoid database-specific features without a documented fallback.
- Add unique constraints for idempotency keys and message IDs. Never rely only on application checks for permanent uniqueness.
- Seeders must be deterministic and development-safe; credentials are documented as development-only.

## Firebase and IoT

- Never use Firestore. Never retain raw telemetry permanently in Firebase.
- Firebase Security Rules must validate authentication claims, active device assignments, trip membership, payload shape, and immutable message IDs.
- Store permanent telemetry and import results in MySQL. Cleanup may delete only synchronized records older than configured retention.
- Firebase credentials, service-account JSON, private keys, device passwords, tokens, and wallet secrets must never be committed.
- Provisioning secrets are returned once, stored only as hashes where Laravel must validate them, and rotated/audited.

## Queues and Scheduling

- Production uses the database queue. Jobs must be idempotent where practical, specify retry/backoff/timeout behavior, avoid serializing unnecessary relationships, and log terminal failures.
- Commands and scheduled work must use bounded batches and locks and tolerate cPanel cron overlap. Do not require Redis, Horizon, Supervisor, Docker, MQTT daemons, Reverb, or permanent workers.

## Blade and Frontend

- Use reusable Blade components, Alpine.js, Tailwind CSS, and Vite. Forms must be accessible and CSRF-protected.
- List pages use server-side filtering, sorting, pagination, empty states, status badges, authorized actions, and bounded exports.
- Escape output by default. Public trace pages use an explicit privacy-safe whitelist.

## Testing and Quality

- Every completed behavior requires tests, including happy path, validation, authorization, organization isolation, idempotency, and important failure behavior.
- Normal tests must use mock Firebase/AI/blockchain drivers and require no live credentials. Add emulator/integration tests separately where useful.
- Before completion run: `composer install`, `php artisan migrate:fresh --seed`, `php artisan route:list`, `php artisan test`, formatter, static analysis, `composer audit`, `npm install`, and `npm run build`.
- Do not claim completion while required tests fail, protected routes lack policies, migrations are missing, buttons are inert, routes are dead, or pages are static placeholders.

## Shared Hosting

- Maintain compatibility with Apache/LiteSpeed, PHP shared hosting, MySQL/MariaDB, database sessions/queues, file/database cache, and cPanel cron.
- Never expose the project root publicly. Production documentation must cover public-directory routing, credentials outside the web root, cron-driven scheduler/queue work, backups, rollback, HTTPS, and `APP_DEBUG=false`.

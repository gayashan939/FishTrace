# Release acceptance status

This document separates automated acceptance already exercised in the repository from environment-dependent checks that must run against release infrastructure.

## Automated and enforced

- Fresh schema migration and deterministic development seed
- Full PHPUnit feature suite with mock Firebase, AI, and blockchain drivers
- PHPStan level 5 with zero accepted errors and Pint formatting
- Blade compilation, route discovery, npm security audit, and Vite production build
- Administrator authorization and privacy tests across every operational console
- Bounded directories, exports, dashboard queues, detail histories, and query-budget coverage
- Responsive administrator shell with mobile navigation, skip link, active-route feedback, keyboard-scrollable tables, accessible control names, visible focus, reduced-motion handling, filter restoration, and clear-filter enhancement
- Private evidence download authorization and audit logging
- Non-destructive `fishtrace:release-check` diagnostics with strict CI/deployment and redacted JSON modes
- Firebase Emulator coverage for default denial, membership/assignment isolation, device identity, immutable telemetry, payload bounds, and schema allowlists
- An opt-in, hard-locked `fishtrace_test` MySQL suite for schema/seed compatibility and two-process last-package sale contention
- Enumeration-safe, single-use password recovery with expiry, attempt limits, session revocation, and security auditing
- AI and blockchain HTTP contract tests covering authentication, bounded retries, malformed responses, sanitized terminal failures, recovery, and verification persistence
- Security hardening coverage for authenticated throttling, transactional file deletion, telemetry failure payload allowlisting/deduplication/recovery, report-failure redaction, secure-cookie/mail/queue diagnostics, and scheduler mutex discovery
- Transport lifecycle tests covering mandatory pre-trip checks, credential rotation, active assignment gating, Firebase reconciliation and cleanup, incidents, delivery confirmation, cancellation, and organization isolation
- Administrator fishing reference-data management with audited non-destructive lifecycle controls and inactive-value enforcement in operational requests
- Configurable cold-chain rules proven against telemetry and scheduled offline monitoring, including duration gating, recovery, acknowledgement, and administrator-only settings
- Administrator system/profile/security pages with an explicit non-secret setting allowlist, current-password verification, and tested session/token revocation

## Required in release infrastructure

- Track the five current moderate npm advisories inherited by the development-only `firebase-tools` chain (`@opentelemetry/core` and nested `uuid`). The high-severity gate passes and these packages are not bundled into the Vite production assets; npm currently proposes only a forced breaking Firebase CLI change, so do not apply it without rerunning the emulator suite.
- Run `composer test:mysql` against the release MySQL/MariaDB version and collation and retain its evidence; the local development machine may not substitute SQLite results.
- Exercise concurrent intake, split, device assignment, inventory reservation, recall, and idempotency requests with separate database connections; concurrent sale protection is now covered by the MySQL harness.
- Run `npm run test:firebase-rules` and retain its evidence; separately verify assignment changes and privileged cleanup through the Laravel Admin SDK because client rules intentionally prohibit those writes.
- Verify queue and scheduler execution through the intended cPanel cron configuration, including overlap/single-server locks, `retry_after > 120`, persistent terminal failures, and controlled retries.
- Perform keyboard-only and screen-reader smoke checks in current Chromium, Firefox, and Safari/WebKit at mobile and desktop widths.
- Verify HTTPS, private storage denial, backups/restores, rollback, credential placement outside the public root, and `APP_DEBUG=false` on staging.
- Run `composer release-check` after `php artisan optimize` and before switching traffic; retain the strict JSON result if the release pipeline supports artifacts.

Production approval should not be recorded until the environment-dependent items have evidence attached to the release record.

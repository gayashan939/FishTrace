# Shared-hosting deployment

## Requirements and upload

Use PHP 8.3+ with ctype, curl, dom, fileinfo, intl, mbstring, openssl, PDO MySQL, sodium, tokenizer, and XML; MySQL 8 or compatible MariaDB; HTTPS; and cPanel cron. Run `composer install --no-dev --optimize-autoloader` locally if SSH/Composer is unavailable. Run `npm ci && npm run build` locally and upload `public/build`.

Keep the Laravel project above `public_html`. Point the domain document root at the application's `public` directory. If the host cannot change document roots, copy only `public` contents to `public_html` and adjust `index.php` paths; never expose `.env`, `vendor`, `storage`, Firebase credentials, or the project root.

## Configure and release

Copy `.env.example` to `.env`; set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, MySQL, database sessions/queues, and file cache. Generate `APP_KEY` once. Set `SESSION_SECURE_COOKIE=true`, keep `SESSION_HTTP_ONLY=true` and `SESSION_SAME_SITE=lax` (or `strict`), configure a real mail transport, use `QUEUE_FAILED_DRIVER=database-uuids`, and keep `DB_QUEUE_RETRY_AFTER` above the longest 120-second job timeout (the supplied value is 180). Put the Firebase service-account JSON outside public web space with mode 600 and set its absolute path in `FIREBASE_CREDENTIALS`. Set real service tokens only in `.env`; enable `AI_AUTO_PREDICT` and `BLOCKCHAIN_AUTO_ANCHOR` when those production integrations are required.

Set `GEOFENCE_ROUTE_CORRIDOR_METERS` and `GEOFENCE_DESTINATION_RADIUS_METERS` for the operational area. Route deviation is measured against the configured origin-to-destination corridor, so allow for the actual road network when selecting the corridor width.

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan fishtrace:release-check --strict
```

The release check is read-only. Run it after the caches and frontend assets are installed; any failed check blocks release. Use `--json` when the deployment system needs a machine-readable artifact. Its output is deliberately credential-safe.

Grant the PHP user write access only to `storage` and `bootstrap/cache`. Do not run demo seeders in production.

## cPanel cron

```cron
* * * * * cd /home/USERNAME/fishtrace && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/USERNAME/fishtrace && /usr/local/bin/php artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
```

The scheduler invokes Firebase telemetry sync each minute; assignment reconciliation, configured offline-device checks, and bounded blockchain verification polling every five minutes; and synchronized cleanup hourly. Every task uses overlap and single-server mutexes, so the cache driver must support atomic locks and be shared if multiple nodes run the scheduler. Test commands manually after deployment. Confirm the exact cPanel PHP binary, log path, timezone, and max execution time. Normal offline checks use the administrator-managed duration; reserve the command's `--minutes` override for controlled diagnostics.

## Firebase and recovery

Deploy rules with `firebase deploy --only database --project PROJECT_ID`, provision users/devices through Laravel, and verify a simulator/emulator message imports once. On failures inspect `storage/logs/laravel.log`, `failed_jobs`, and `firebase_sync_failures`; persisted and Firebase-visible failures are intentionally sanitized while full exceptions remain only in protected logs. Retry failed telemetry with `fishtrace:sync-firebase-telemetry --retry-failed`; never solve failures by opening database rules.

Back up MySQL and private uploaded storage as one recovery set before each release, and restore both to the same recovery point. Roll back application files atomically and use a reviewed down/forward migration strategy; destructive production migration rollback is not automatic. Test Sanctum CORS, authenticated rate-limit responses, HTTPS/cookie headers, mail, cron, failed-job persistence/retry, and backup restoration before go-live.

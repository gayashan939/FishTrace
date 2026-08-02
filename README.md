# FishTrace

FishTrace is a Laravel 13 traceability platform for Sri Lankan fish supply chains. The implemented milestone covers Sanctum mobile login, Firebase custom sessions, fisher boat/trip/catch/batch workflows, secure QR labels, transport/device assignment mirroring, ESP32-style telemetry simulation and import, permanent MySQL sensor history, a consolidated administrator dashboard plus batch, fishing, transport, IoT, processor, retail, compliance, and reporting operations, and a privacy-filtered consumer trace portal.

## Local setup

Requirements: PHP 8.3+, Composer 2, Node 20+, and MySQL 8/MariaDB. SQLite is supported for automated tests.

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure MySQL in .env
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Demo password (development only): `FishTrace@2026`. Accounts are `admin@fishtrace.demo`, `fisher@fishtrace.demo`, `processor@fishtrace.demo`, `transporter@fishtrace.demo`, `retailer@fishtrace.demo`, and `inspector@fishtrace.demo`.

- Admin: `/admin/login`
- Demo consumer trace: `/trace/demo-trace-yellowfin-tuna-2026`
- Flutter API base: `/api/v1`
- API contract: [docs/api-contract.md](docs/api-contract.md)
- Deployment: [docs/shared-hosting-deployment.md](docs/shared-hosting-deployment.md)

Mock Firebase, AI, and blockchain drivers are the safe development defaults. Never commit production credentials. The implementation status is tracked honestly in [docs/implementation-checklist.md](docs/implementation-checklist.md).

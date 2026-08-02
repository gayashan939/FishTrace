<?php

namespace App\Services\Operations;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReleaseReadiness
{
    /**
     * @return list<array{key: string, label: string, status: 'pass'|'warn'|'fail', message: string}>
     */
    public function inspect(bool $strict = false): array
    {
        $checks = [];
        $advisory = $strict ? 'fail' : 'warn';

        $this->add($checks, 'environment', 'Application environment', app()->environment('production') ? 'pass' : $advisory, app()->environment('production') ? 'Production mode is enabled.' : 'APP_ENV is not production.');
        $this->add($checks, 'debug', 'Debug mode', config('app.debug') === false ? 'pass' : $advisory, config('app.debug') === false ? 'Debug output is disabled.' : 'APP_DEBUG must be false for release.');
        $this->add($checks, 'https', 'Canonical URL', str_starts_with((string) config('app.url'), 'https://') ? 'pass' : $advisory, str_starts_with((string) config('app.url'), 'https://') ? 'APP_URL uses HTTPS.' : 'APP_URL should use HTTPS.');
        $this->add($checks, 'app_key', 'Application key', filled(config('app.key')) ? 'pass' : 'fail', filled(config('app.key')) ? 'An application key is configured.' : 'APP_KEY is missing.');
        $key = (string) config('app.key');
        $decodedKey = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $this->add($checks, 'app_key_strength', 'Application key strength', is_string($decodedKey) && strlen($decodedKey) === 32 ? 'pass' : 'fail', is_string($decodedKey) && strlen($decodedKey) === 32 ? 'APP_KEY has the required encryption-key length.' : 'APP_KEY must contain a valid 32-byte key.');
        $this->add($checks, 'maintenance', 'Maintenance mode', app()->isDownForMaintenance() ? 'fail' : 'pass', app()->isDownForMaintenance() ? 'The application is in maintenance mode.' : 'The application is accepting requests.');

        $this->databaseChecks($checks, $advisory);
        $this->driverChecks($checks, $advisory);
        $this->filesystemChecks($checks);
        $this->cacheChecks($checks, $advisory);
        $this->securityChecks($checks, $advisory);
        $this->integrationChecks($checks, $advisory);

        return $checks;
    }

    /**
     * @param  list<array{key: string, label: string, status: 'pass'|'warn'|'fail', message: string}>  $checks
     */
    private function databaseChecks(array &$checks, string $advisory): void
    {
        try {
            DB::select('select 1');
            $this->add($checks, 'database_connection', 'Database connection', 'pass', 'The configured database is reachable.');

            $driver = DB::connection()->getDriverName();
            $this->add($checks, 'database_driver', 'Database platform', $driver === 'mysql' ? 'pass' : $advisory, $driver === 'mysql' ? 'MySQL is configured.' : 'Release validation expects MySQL.');
            if ($driver === 'mysql') {
                $connection = config('database.default');
                $username = config("database.connections.{$connection}.username");
                $password = config("database.connections.{$connection}.password");
                $database = config("database.connections.{$connection}.database");
                $validCredentials = filled($username) && filled($password) && filled($database) && $database !== 'fishtrace_test';
                $this->add($checks, 'database_credentials', 'Database credentials', $validCredentials ? 'pass' : 'fail', $validCredentials ? 'A named non-test database and authenticated user are configured.' : 'Configure a non-test database with a dedicated username and password.');
            }

            $required = ['users', 'fish_batches', 'jobs', 'failed_jobs', 'audit_logs', 'sensor_readings'];
            $missing = array_values(array_filter($required, fn (string $table): bool => ! Schema::hasTable($table)));
            $this->add($checks, 'database_schema', 'Required schema', $missing === [] ? 'pass' : 'fail', $missing === [] ? 'Required operational tables exist.' : 'Required database migrations are incomplete.');
        } catch (Throwable) {
            $this->add($checks, 'database_connection', 'Database connection', 'fail', 'The configured database could not be queried.');
        }
    }

    /**
     * @param  list<array{key: string, label: string, status: 'pass'|'warn'|'fail', message: string}>  $checks
     */
    private function driverChecks(array &$checks, string $advisory): void
    {
        $session = (string) config('session.driver');
        $queue = (string) config('queue.default');
        $cache = (string) config('cache.default');

        $this->add($checks, 'session_driver', 'Session driver', in_array($session, ['database', 'redis'], true) ? 'pass' : $advisory, in_array($session, ['database', 'redis'], true) ? 'A persistent session driver is configured.' : 'Use database or Redis sessions for release.');
        $this->add($checks, 'queue_driver', 'Queue driver', in_array($queue, ['database', 'redis', 'sqs'], true) ? 'pass' : $advisory, in_array($queue, ['database', 'redis', 'sqs'], true) ? 'An asynchronous queue is configured.' : 'Use an asynchronous queue worker for release.');
        $this->add($checks, 'cache_driver', 'Cache driver', ! in_array($cache, ['array', 'null'], true) ? 'pass' : $advisory, ! in_array($cache, ['array', 'null'], true) ? 'A persistent cache store is configured.' : 'Use a persistent cache store for release.');
    }

    /**
     * @param  list<array{key: string, label: string, status: 'pass'|'warn'|'fail', message: string}>  $checks
     */
    private function filesystemChecks(array &$checks): void
    {
        $privateRoot = (string) config('filesystems.disks.local.root');
        $publicRoot = realpath(public_path()) ?: public_path();
        $resolvedPrivate = realpath($privateRoot) ?: $privateRoot;
        $outsidePublic = $privateRoot !== '' && strtolower($resolvedPrivate) !== strtolower($publicRoot) && ! str_starts_with(strtolower($resolvedPrivate), strtolower($publicRoot.DIRECTORY_SEPARATOR));

        $this->add($checks, 'private_storage', 'Private file storage', $outsidePublic && is_dir($privateRoot) && is_writable($privateRoot) ? 'pass' : 'fail', $outsidePublic && is_dir($privateRoot) && is_writable($privateRoot) ? 'Private storage exists, is writable, and is outside the public root.' : 'Private storage must be writable and outside the public root.');

        $runtimePaths = [storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')];
        $runtimeReady = collect($runtimePaths)->every(fn (string $path): bool => is_dir($path) && is_writable($path));
        $this->add($checks, 'runtime_storage', 'Runtime directories', $runtimeReady ? 'pass' : 'fail', $runtimeReady ? 'Laravel runtime directories are writable.' : 'Storage and bootstrap cache directories must be writable.');
        $this->add($checks, 'frontend_build', 'Frontend build', is_file(public_path('build/manifest.json')) ? 'pass' : 'fail', is_file(public_path('build/manifest.json')) ? 'The Vite production manifest exists.' : 'Run the production frontend build before release.');
    }

    /**
     * @param  list<array{key: string, label: string, status: 'pass'|'warn'|'fail', message: string}>  $checks
     */
    private function cacheChecks(array &$checks, string $advisory): void
    {
        /** @var Application $application */
        $application = app();
        $optimized = $application->configurationIsCached() && $application->routesAreCached();
        $this->add($checks, 'optimization_cache', 'Deployment caches', $optimized ? 'pass' : $advisory, $optimized ? 'Configuration and routes are cached.' : 'Run artisan optimize during deployment.');

        $retention = (int) config('fishtrace.audit.retention_days', 0);
        $this->add($checks, 'audit_retention', 'Audit retention', $retention >= 2555 ? 'pass' : $advisory, $retention >= 2555 ? 'Audit retention is at least seven years.' : 'Audit retention is below the seven-year release baseline.');
    }

    /**
     * @param  list<array{key: string, label: string, status: 'pass'|'warn'|'fail', message: string}>  $checks
     */
    private function securityChecks(array &$checks, string $advisory): void
    {
        $secureCookie = config('session.secure') === true;
        $this->add($checks, 'secure_session_cookie', 'Secure session cookie', $secureCookie ? 'pass' : $advisory, $secureCookie ? 'Session cookies require HTTPS.' : 'Set SESSION_SECURE_COOKIE=true for release.');
        $cookiePolicy = config('session.http_only') === true && in_array(config('session.same_site'), ['lax', 'strict'], true);
        $this->add($checks, 'session_cookie_policy', 'Session cookie policy', $cookiePolicy ? 'pass' : 'fail', $cookiePolicy ? 'Session cookies are HTTP-only with a CSRF-resistant SameSite policy.' : 'Session cookies must be HTTP-only and use SameSite lax or strict.');

        $mailDriver = (string) config('mail.default');
        $productionMail = ! in_array($mailDriver, ['array', 'log'], true);
        $this->add($checks, 'mail_driver', 'Mail delivery', $productionMail ? 'pass' : $advisory, $productionMail ? 'A production mail transport is configured.' : 'Configure a production mail transport instead of array/log.');

        $failedDriver = (string) config('queue.failed.driver');
        $this->add($checks, 'failed_job_storage', 'Failed-job storage', $failedDriver !== 'null' ? 'pass' : 'fail', $failedDriver !== 'null' ? 'Terminal queue failures are persisted.' : 'QUEUE_FAILED_DRIVER must persist failed jobs.');
        if (config('queue.default') === 'database') {
            $retryAfter = (int) config('queue.connections.database.retry_after');
            $this->add($checks, 'queue_retry_window', 'Queue retry window', $retryAfter > 120 ? 'pass' : 'fail', $retryAfter > 120 ? 'Database queue retry_after exceeds the longest job timeout.' : 'DB_QUEUE_RETRY_AFTER must exceed the 120-second maximum job timeout.');
        } else {
            $this->add($checks, 'queue_retry_window', 'Queue retry window', $advisory, 'The database queue retry window is checked when QUEUE_CONNECTION=database.');
        }
    }

    /**
     * @param  list<array{key: string, label: string, status: 'pass'|'warn'|'fail', message: string}>  $checks
     */
    private function integrationChecks(array &$checks, string $advisory): void
    {
        $labels = ['firebase' => 'Firebase', 'ai' => 'AI', 'blockchain' => 'Blockchain'];
        foreach ($labels as $integration => $label) {
            $driver = (string) config("fishtrace.{$integration}.driver", 'mock');
            $this->add($checks, "{$integration}_driver", $label.' integration', $driver !== 'mock' ? 'pass' : $advisory, $driver !== 'mock' ? 'A non-mock integration driver is configured.' : 'The mock driver is still configured.');
        }

        if (config('fishtrace.firebase.driver') !== 'mock') {
            $credentials = (string) config('fishtrace.firebase.credentials');
            $databaseUrl = (string) config('fishtrace.firebase.database_url');
            $credentialPath = realpath($credentials) ?: $credentials;
            $publicRoot = realpath(public_path()) ?: public_path();
            $outsidePublic = $credentialPath !== $publicRoot && ! str_starts_with(strtolower($credentialPath), strtolower($publicRoot.DIRECTORY_SEPARATOR));
            $valid = $credentials !== '' && is_file($credentials) && is_readable($credentials) && $outsidePublic && str_starts_with($databaseUrl, 'https://');
            $this->add($checks, 'firebase_configuration', 'Firebase credentials', $valid ? 'pass' : 'fail', $valid ? 'Firebase credentials outside the public root and an HTTPS database URL are available.' : 'Firebase credentials must be readable outside the public root and use an HTTPS database URL.');
        }

        foreach (['ai' => 'AI', 'blockchain' => 'Blockchain'] as $integration => $label) {
            if (config("fishtrace.{$integration}.driver") === 'mock') {
                continue;
            }

            $url = (string) config("fishtrace.{$integration}.url");
            $valid = str_starts_with($url, 'https://') && filled(config("fishtrace.{$integration}.token"));
            $this->add($checks, "{$integration}_configuration", $label.' endpoint', $valid ? 'pass' : 'fail', $valid ? 'An HTTPS endpoint and authentication token are configured.' : 'An HTTPS endpoint and authentication token are required.');
        }

        if (config('fishtrace.ai.driver') !== 'mock') {
            $enabled = config('fishtrace.ai.auto_predict') === true;
            $this->add($checks, 'ai_automation', 'AI automation', $enabled ? 'pass' : 'fail', $enabled ? 'Telemetry-triggered prediction is enabled.' : 'AI_AUTO_PREDICT must be enabled for release.');
        }
        if (config('fishtrace.blockchain.driver') !== 'mock') {
            $enabled = config('fishtrace.blockchain.auto_anchor') === true;
            $this->add($checks, 'blockchain_automation', 'Blockchain automation', $enabled ? 'pass' : 'fail', $enabled ? 'Approved traceability milestones are automatically anchored.' : 'BLOCKCHAIN_AUTO_ANCHOR must be enabled for release.');
        }
    }

    /**
     * @param  list<array{key: string, label: string, status: 'pass'|'warn'|'fail', message: string}>  $checks
     * @param  'pass'|'warn'|'fail'  $status
     */
    private function add(array &$checks, string $key, string $label, string $status, string $message): void
    {
        $checks[] = compact('key', 'label', 'status', 'message');
    }
}

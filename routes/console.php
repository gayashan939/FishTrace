<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('fishtrace:sync-firebase-telemetry')->everyMinute()->withoutOverlapping(2)->onOneServer();
Schedule::command('fishtrace:cleanup-firebase-telemetry')->hourly()->withoutOverlapping(10)->onOneServer();
Schedule::command('fishtrace:check-offline-devices')->everyFiveMinutes()->withoutOverlapping(5)->onOneServer();
Schedule::command('fishtrace:reconcile-firebase-assignments')->everyFiveMinutes()->withoutOverlapping(5)->onOneServer();
Schedule::command('fishtrace:poll-blockchain-transactions')->everyFiveMinutes()->withoutOverlapping(5)->onOneServer();
Schedule::command('fishtrace:prune-audit-logs')->monthly()->withoutOverlapping(30)->onOneServer();

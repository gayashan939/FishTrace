<?php

namespace App\Providers;

use App\Models\BatchIntake;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\ColdChainAlert;
use App\Models\DeviceAssignment;
use App\Models\FileAsset;
use App\Models\FishBatch;
use App\Models\FishingTrip;
use App\Models\InventoryLot;
use App\Models\IotDevice;
use App\Models\Organization;
use App\Models\ProcessingRecord;
use App\Models\ProcessingStep;
use App\Models\QualityInspection;
use App\Models\ReportExport;
use App\Models\RetailReceipt;
use App\Models\RetailSale;
use App\Models\TraceabilityEvent;
use App\Models\TransportTrip;
use App\Models\User;
use App\Observers\AuditableModelObserver;
use App\Observers\TraceabilityEventObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('authenticated-api', fn (Request $request) => Limit::perMinute($request->isMethodSafe() ? 600 : 120)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        foreach ([User::class, Organization::class, Boat::class, FishingTrip::class, CatchRecord::class, FishBatch::class, BatchIntake::class, ProcessingRecord::class, ProcessingStep::class, QualityInspection::class, TransportTrip::class, DeviceAssignment::class, IotDevice::class, ColdChainAlert::class, RetailReceipt::class, InventoryLot::class, RetailSale::class, FileAsset::class, ReportExport::class] as $model) {
            $model::observe(AuditableModelObserver::class);
        }
        TraceabilityEvent::observe(TraceabilityEventObserver::class);
    }
}

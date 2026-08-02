<?php

namespace App\Services\Consumer;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishBatch;
use App\Models\FishingTrip;
use App\Models\FishSpecies;
use App\Models\QrCode;
use App\Models\TraceabilityEvent;
use App\Services\Blockchain\PublicBlockchainStatus;
use App\Services\Settings\SystemSettings;
use Illuminate\Support\Carbon;

class PublicTraceView
{
    public function __construct(
        private readonly SystemSettings $settings,
        private readonly PublicBlockchainStatus $blockchain,
    ) {}

    public function build(string $token): array
    {
        abort_unless($this->settings->boolean('consumer_portal_enabled'), 503, 'The consumer trace portal is temporarily unavailable.');
        $qr = QrCode::query()->where('public_token', $token)->firstOrFail();
        abort_if($qr->revoked_at !== null, 410, 'This QR code has been revoked.');
        $batch = FishBatch::query()->findOrFail($qr->fish_batch_id);
        abort_unless($batch->is_public, 404);
        $species = FishSpecies::query()->findOrFail($batch->fish_species_id);
        $catch = CatchRecord::query()->whereHas('batches', fn ($query) => $query->whereKey($batch->id))->first();
        $trip = $catch ? FishingTrip::query()->find($catch->fishing_trip_id) : null;
        $boat = $trip ? Boat::query()->find($trip->boat_id) : null;
        $events = TraceabilityEvent::query()->where('fish_batch_id', $batch->id)->orderBy('occurred_at')->get();
        $blockchainStatus = $this->blockchain->forBatch($batch);

        return [
            'verification_status' => $batch->is_recalled ? 'BATCH_RECALLED' : ($blockchainStatus['status'] === 'VERIFIED' ? 'VERIFIED' : 'TRACE_RECORDED'),
            'verified_at' => now()->toIso8601String(),
            'batch' => ['code' => $batch->batch_code, 'species' => $species->common_name, 'scientific_name' => $species->scientific_name, 'product_type' => $batch->product_type, 'weight_kg' => $batch->total_weight_kg, 'status' => $batch->getRawOriginal('status'), 'recalled' => $batch->is_recalled],
            'origin' => ['catch_date' => $catch ? Carbon::parse($catch->getAttribute('caught_at'))->toDateString() : null, 'general_area' => $trip?->general_catch_area, 'vessel' => $boat?->name],
            'cold_chain' => ['summary' => 'Temperature-controlled history is retained in FishTrace.', 'full_stream_public' => false],
            'blockchain' => $blockchainStatus,
            'portal' => ['name' => $this->settings->get('platform_name'), 'notice' => $this->settings->get('consumer_portal_notice'), 'support_email' => $this->settings->get('support_email')],
            'timeline' => $events->map(fn (TraceabilityEvent $event): array => ['type' => $event->event_type, 'title' => $event->title, 'details' => $event->public_data, 'occurred_at' => Carbon::parse($event->getAttribute('occurred_at'))->toIso8601String()])->all(),
        ];
    }
}

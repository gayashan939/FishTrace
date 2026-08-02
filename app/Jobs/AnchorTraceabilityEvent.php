<?php

namespace App\Jobs;

use App\Models\TraceabilityEvent;
use App\Services\Blockchain\TraceabilityAnchorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AnchorTraceabilityEvent implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public array $backoff = [30, 120, 300, 900];

    public int $uniqueFor = 1800;

    public function __construct(public string $eventId) {}

    public function uniqueId(): string
    {
        return $this->eventId;
    }

    public function handle(TraceabilityAnchorService $service): void
    {
        $service->anchor(TraceabilityEvent::findOrFail($this->eventId));
    }

    public function failed(Throwable $exception): void
    {
        $event = TraceabilityEvent::find($this->eventId);
        if ($event !== null) {
            app(TraceabilityAnchorService::class)->recordFailure($event, $exception, max(1, $this->attempts()));
        }
    }
}

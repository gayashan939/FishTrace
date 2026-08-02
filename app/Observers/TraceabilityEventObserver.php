<?php

namespace App\Observers;

use App\Jobs\AnchorTraceabilityEvent;
use App\Models\TraceabilityEvent;
use App\Services\Blockchain\TraceabilityAnchorPolicy;

class TraceabilityEventObserver
{
    public function __construct(private TraceabilityAnchorPolicy $policy) {}

    public function created(TraceabilityEvent $event): void
    {
        if (! config('fishtrace.blockchain.auto_anchor') || ! $this->policy->supports($event->event_type)) {
            return;
        }

        AnchorTraceabilityEvent::dispatch($event->id)->afterCommit();
    }
}

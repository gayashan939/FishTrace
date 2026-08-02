<?php

namespace App\Services\Admin;

use App\Models\BlockchainTransaction;
use App\Models\ChildBatch;
use App\Models\FileAsset;
use App\Models\FishBatch;
use App\Models\QualityInspection;
use Illuminate\Support\Facades\DB;

class ComplianceOperationsView
{
    public function incident(QualityInspection $inspection): array
    {
        abort_unless(in_array($inspection->getRawOriginal('result'), ['FAILED', 'CONDITIONAL'], true), 404);
        $inspection->load(['batch.organization:id,name,code', 'batch.inventoryLots.organization:id,name', 'batch.inventoryLots.location:id,name', 'batch.inventoryLots.label:id,label_code', 'batch.childLinks.child.inventoryLots.organization:id,name', 'batch.childLinks.child.inventoryLots.location:id,name', 'batch.childLinks.child.inventoryLots.label:id,label_code', 'organization:id,name,code', 'inspector:id,name,email', 'qualityGrade:id,code,name', 'processingRecord.steps.performer:id,name']);
        $batch = $inspection->batch;
        abort_unless($batch instanceof FishBatch, 404);
        $affectedLots = $batch->inventoryLots;
        foreach ($batch->childLinks as $link) {
            if (! $link instanceof ChildBatch) {
                continue;
            } $child = $link->child;
            if ($child instanceof FishBatch) {
                $affectedLots = $affectedLots->concat($child->inventoryLots);
            }
        }
        $evidence = FileAsset::query()->where('entity_type', 'quality_inspection')->where('entity_id', $inspection->id)->with(['organization:id,name', 'uploader:id,name'])->latest()->limit(100)->get();

        return compact('inspection', 'evidence', 'affectedLots');
    }

    public function blockchain(BlockchainTransaction $transaction): array
    {
        $transaction->load(['verifications' => fn ($q) => $q->latest('verified_at')->limit(100)]);
        $anchor = DB::table('blockchain_event_anchors')->join('traceability_events', 'traceability_events.id', '=', 'blockchain_event_anchors.traceability_event_id')->join('fish_batches', 'fish_batches.id', '=', 'traceability_events.fish_batch_id')->where('blockchain_event_anchors.blockchain_transaction_id', $transaction->id)->select(['traceability_events.id as event_id', 'traceability_events.event_type', 'traceability_events.title', 'traceability_events.occurred_at', 'fish_batches.id as batch_id', 'fish_batches.batch_code'])->first();

        return compact('transaction', 'anchor');
    }
}

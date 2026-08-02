<?php

namespace App\Services\Admin;

use App\Models\AIPrediction;
use App\Models\AIServiceFailure;
use App\Models\BlockchainTransaction;
use App\Models\FileAsset;
use App\Models\FishBatch;
use App\Models\QualityInspection;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class ComplianceOperationsQuery
{
    public function incidents(array $f): Builder
    {
        return QualityInspection::query()->whereIn('result', ['FAILED', 'CONDITIONAL'])->with(['batch:id,batch_code,status,is_recalled,product_type', 'organization:id,name,code', 'inspector:id,name', 'qualityGrade:id,code,name', 'processingRecord:id,status'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->whereHas('batch', fn (Builder $b): Builder => $b->where('batch_code', 'like', "%{$term}%")))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when($f['result'] ?? null, fn (Builder $q, string $result): Builder => $q->where('result', $result))->when($f['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('inspected_at', '>=', $date.' 00:00:00'))->when($f['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('inspected_at', '<=', $date.' 23:59:59'))->latest('inspected_at');
    }

    public function recalls(array $f): Builder
    {
        return FishBatch::query()->where(fn (Builder $q): Builder => $q->where('is_recalled', true)->orWhereHas('inventoryLots', fn (Builder $lots): Builder => $lots->where('status', 'RECALLED')))->with(['organization:id,name,code', 'species:id,common_name', 'inventoryLots' => fn ($q) => $q->where('status', 'RECALLED')->with(['organization:id,name', 'location:id,name', 'label:id,label_code'])])->withCount(['inventoryLots as recalled_lots_count' => fn ($q) => $q->where('status', 'RECALLED'), 'events'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where('batch_code', 'like', "%{$term}%"))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where(fn (Builder $q): Builder => $q->where('organization_id', $id)->orWhereHas('inventoryLots', fn (Builder $lots): Builder => $lots->where('organization_id', $id))))->latest('updated_at');
    }

    public function evidence(array $f): Builder
    {
        return FileAsset::query()->with(['organization:id,name,code', 'uploader:id,name,email'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where('original_name', 'like', "%{$term}%"))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when($f['category'] ?? null, fn (Builder $q, string $category): Builder => $q->where('category', $category))->when($f['entity_type'] ?? null, fn (Builder $q, string $type): Builder => $q->where('entity_type', $type))->when($f['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('created_at', '>=', $date.' 00:00:00'))->when($f['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('created_at', '<=', $date.' 23:59:59'))->latest();
    }

    public function notifications(array $f): Builder
    {
        return DatabaseNotification::query()->select(['notifications.*', 'users.name as recipient_name', 'users.email as recipient_email', 'organizations.name as organization_name'])->join('users', 'users.id', '=', 'notifications.notifiable_id')->leftJoin('organization_user', 'organization_user.user_id', '=', 'users.id')->leftJoin('organizations', 'organizations.id', '=', 'organization_user.organization_id')->where('notifications.notifiable_type', User::class)->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('users.name', 'like', "%{$term}%")->orWhere('users.email', 'like', "%{$term}%")))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organizations.id', $id))->when($f['status'] ?? null, fn (Builder $q, string $type): Builder => $q->where('notifications.data', 'like', '%"type":"'.$type.'"%'))->when($f['read'] ?? null, fn (Builder $q, string $read): Builder => $read === 'read' ? $q->whereNotNull('notifications.read_at') : $q->whereNull('notifications.read_at'))->latest('notifications.created_at');
    }

    public function reports(array $f): Builder
    {
        return ReportExport::query()->with(['organization:id,name,code', 'requester:id,name,email', 'file:id,entity_id,original_name,mime_type,size_bytes'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where('report_type', 'like', "%{$term}%"))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when($f['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->latest();
    }

    public function predictions(array $f): Builder
    {
        return AIPrediction::query()->with(['batch:id,batch_code,status,organization_id', 'batch.organization:id,name,code', 'trip:id,trip_code', 'requester:id,name'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->whereHas('batch', fn (Builder $b): Builder => $b->where('batch_code', 'like', "%{$term}%")))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->whereHas('batch', fn (Builder $b): Builder => $b->where('organization_id', $id)))->when($f['risk_level'] ?? null, fn (Builder $q, string $risk): Builder => $q->where('risk_level', $risk))->latest('predicted_at');
    }

    public function aiFailures(array $f): Builder
    {
        return AIServiceFailure::query()->with(['batch:id,batch_code,organization_id', 'batch.organization:id,name'])->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->whereHas('batch', fn (Builder $b): Builder => $b->where('organization_id', $id)))->when($f['status'] ?? null, fn (Builder $q, string $status): Builder => $status === 'RESOLVED' ? $q->whereNotNull('resolved_at') : $q->whereNull('resolved_at'))->latest();
    }

    public function blockchain(array $f): Builder
    {
        return BlockchainTransaction::query()->withCount('verifications')->addSelect(['batch_code' => \DB::table('blockchain_event_anchors')->join('traceability_events', 'traceability_events.id', '=', 'blockchain_event_anchors.traceability_event_id')->join('fish_batches', 'fish_batches.id', '=', 'traceability_events.fish_batch_id')->select('fish_batches.batch_code')->whereColumn('blockchain_event_anchors.blockchain_transaction_id', 'blockchain_transactions.id')->limit(1), 'event_type' => \DB::table('blockchain_event_anchors')->join('traceability_events', 'traceability_events.id', '=', 'blockchain_event_anchors.traceability_event_id')->select('traceability_events.event_type')->whereColumn('blockchain_event_anchors.blockchain_transaction_id', 'blockchain_transactions.id')->limit(1)])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('transaction_reference', 'like', "%{$term}%")->orWhere('event_hash', 'like', "%{$term}%")))->when($f['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->latest();
    }
}

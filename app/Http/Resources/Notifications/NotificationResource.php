<?php

namespace App\Http\Resources\Notifications;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use LogicException;

class NotificationResource extends JsonResource
{
    private const CONTEXT_KEYS = [
        'alert_id', 'batch_id', 'blockchain_transaction_id', 'confidence', 'device_assignment_id',
        'device_id', 'failure_id', 'inspection_id', 'intake_id', 'inventory_lot_id', 'last_seen_at',
        'measured_value', 'message_id', 'prediction_id', 'processing_record_id', 'report_export_id',
        'result', 'retail_receipt_id', 'threshold_minutes', 'threshold_value', 'traceability_event_id',
        'transport_trip_id',
    ];

    public function toArray(Request $request): array
    {
        $notification = $this->model();
        $data = $notification->data;
        $context = is_array($data['context'] ?? null) ? $data['context'] : [];

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => [
                'type' => $data['type'] ?? null,
                'title' => $data['title'] ?? null,
                'message' => $data['message'] ?? null,
                'context' => array_intersect_key($context, array_flip(self::CONTEXT_KEYS)),
            ],
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
            'updated_at' => $notification->updated_at,
        ];
    }

    private function model(): DatabaseNotification
    {
        if (! $this->resource instanceof DatabaseNotification) {
            throw new LogicException('NotificationResource requires a DatabaseNotification model.');
        }

        return $this->resource;
    }
}

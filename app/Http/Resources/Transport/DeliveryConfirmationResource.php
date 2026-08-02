<?php

namespace App\Http\Resources\Transport;

use App\Models\DeliveryConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class DeliveryConfirmationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $confirmation = $this->model();

        return [
            'id' => $confirmation->id,
            'transport_trip_id' => $confirmation->transport_trip_id,
            'confirmed_by' => $confirmation->confirmed_by,
            'receiver_name' => $confirmation->receiver_name,
            'receiver_contact' => $confirmation->receiver_contact,
            'notes' => $confirmation->notes,
            'delivered_at' => $confirmation->delivered_at,
            'created_at' => $confirmation->created_at,
            'updated_at' => $confirmation->updated_at,
        ];
    }

    private function model(): DeliveryConfirmation
    {
        if (! $this->resource instanceof DeliveryConfirmation) {
            throw new LogicException('DeliveryConfirmationResource requires a DeliveryConfirmation model.');
        }

        return $this->resource;
    }
}

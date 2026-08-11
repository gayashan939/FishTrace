<?php

namespace App\Http\Resources\Retail;

use App\Http\Resources\Fisher\FishBatchResource;
use App\Models\PackageLabel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class PackageLabelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $label = $this->model();

        return [
            'id' => $label->id,
            'fish_batch_id' => $label->fish_batch_id,
            'label_code' => $label->label_code,
            'trace_url' => url('/trace/'.$label->getRawOriginal('public_token')),
            'package_weight_kg' => $label->package_weight_kg,
            'package_count' => $label->package_count,
            'printed_at' => $label->printed_at,
            'created_at' => $label->created_at,
            'updated_at' => $label->updated_at,
            'batch' => $this->when($label->relationLoaded('batch'), fn () => new FishBatchResource($label->batch)),
        ];
    }

    private function model(): PackageLabel
    {
        if (! $this->resource instanceof PackageLabel) {
            throw new LogicException('PackageLabelResource requires a PackageLabel model.');
        }

        return $this->resource;
    }
}

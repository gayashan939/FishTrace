<?php

namespace App\Http\Requests\Fisher;

use App\Models\FishBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FishBatch::class) ?? false;
    }

    public function rules(): array
    {
        return ['fish_species_id' => ['required', 'uuid', Rule::exists('fish_species', 'id')->where('is_active', true)], 'product_type' => ['required', 'string', 'max:100'], 'catches' => ['required', 'array', 'min:1'], 'catches.*.catch_id' => ['required', 'uuid', 'distinct', 'exists:catch_records,id'], 'catches.*.weight_kg' => ['required', 'numeric', 'gt:0']];
    }
}

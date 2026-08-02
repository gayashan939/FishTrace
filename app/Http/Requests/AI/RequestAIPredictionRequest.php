<?php

namespace App\Http\Requests\AI;

use App\Models\FishBatch;
use Illuminate\Foundation\Http\FormRequest;

class RequestAIPredictionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch instanceof FishBatch && ($this->user()?->can('view', $batch) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}

<?php

namespace App\Http\Requests\Processor;

use App\Models\ProcessingRecord;
use Illuminate\Foundation\Http\FormRequest;

class StartProcessingStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('processingRecord');

        return $record instanceof ProcessingRecord && ($this->user()?->can('update', $record) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}

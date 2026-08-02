<?php

namespace App\Http\Requests\Transport;

use App\Services\Transport\TransportChecklistService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransportChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('transportTrip');

        return $trip !== null && ($this->user()?->can('update', $trip) ?? false);
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:'.count(TransportChecklistService::ITEMS)],
            'items.*.key' => ['required', 'string', Rule::in(array_keys(TransportChecklistService::ITEMS)), 'distinct'],
            'items.*.completed' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, bool> */
    public function itemStates(): array
    {
        return collect($this->validated('items'))->mapWithKeys(fn (array $item): array => [$item['key'] => (bool) $item['completed']])->all();
    }
}

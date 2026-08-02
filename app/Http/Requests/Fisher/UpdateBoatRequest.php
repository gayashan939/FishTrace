<?php

namespace App\Http\Requests\Fisher;

use App\Models\Boat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBoatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $boat = $this->route('boat');

        return $boat instanceof Boat && ($this->user()?->can('update', $boat) ?? false);
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:50', Rule::unique('boats')->ignore($this->route('boat'))],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:80'],
            'capacity_kg' => ['nullable', 'numeric', 'gt:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

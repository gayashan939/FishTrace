<?php

namespace App\Http\Requests\Fisher;

use App\Models\Boat;
use Illuminate\Foundation\Http\FormRequest;

class DeleteBoatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $boat = $this->route('boat');

        return $boat instanceof Boat && ($this->user()?->can('delete', $boat) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}

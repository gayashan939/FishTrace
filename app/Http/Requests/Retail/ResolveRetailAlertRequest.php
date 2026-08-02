<?php

namespace App\Http\Requests\Retail;

use App\Models\ColdChainAlert;
use Illuminate\Foundation\Http\FormRequest;

class ResolveRetailAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        $alert = $this->route('alert');

        return $alert instanceof ColdChainAlert && ($this->user()?->can('manageForRetailer', $alert) ?? false);
    }

    public function rules(): array
    {
        return ['note' => ['required', 'string', 'min:5', 'max:1000']];
    }
}

<?php

namespace App\Http\Requests\Retail;

use App\Models\ColdChainAlert;
use Illuminate\Foundation\Http\FormRequest;

class QuarantineRetailAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        $alert = $this->route('alert');

        return $alert instanceof ColdChainAlert && ($this->user()?->can('manageForRetailer', $alert) ?? false);
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:500']];
    }
}

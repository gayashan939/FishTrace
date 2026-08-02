<?php

namespace App\Http\Requests\Fisher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFishingTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('fishingTrip')) ?? false;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->primaryOrganization()?->id;
        $userId = $this->user()?->id;

        return [
            'boat_id' => ['required', 'uuid', Rule::exists('boats', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)->where('owner_id', $userId)->where('is_active', true)->whereNull('deleted_at'))],
            'landing_site_id' => ['nullable', 'uuid', Rule::exists('landing_sites', 'id')->where('is_active', true)],
            'general_catch_area' => ['nullable', 'string', 'max:160'],
        ];
    }
}

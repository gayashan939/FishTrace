<?php

namespace App\Http\Requests\Fisher;

use App\Models\FishingTrip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFishingTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FishingTrip::class) ?? false;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->primaryOrganization()?->id;
        $userId = $this->user()?->id;

        return [
            'boat_id' => ['required', 'uuid', Rule::exists('boats', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)->where('owner_id', $userId)->where('is_active', true)->whereNull('deleted_at'))],
            'landing_site_id' => ['nullable', 'uuid', Rule::exists('landing_sites', 'id')->where('is_active', true)],
            'trip_code' => ['nullable', 'string', 'max:50', Rule::unique('fishing_trips', 'trip_code')],
            'client_record_id' => ['nullable', 'uuid'],
            'general_catch_area' => ['nullable', 'string', 'max:160'],
            'planned_departure_at' => ['nullable', 'date'],
            'expected_duration_hours' => ['nullable', 'numeric', 'gt:0', 'max:9999.99'],
            'fishing_area_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:fishing_area_longitude'],
            'fishing_area_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:fishing_area_latitude'],
            'crew' => ['nullable', 'array', 'max:50'],
            'crew.*' => ['required', 'string', 'max:120', 'distinct'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;

class FishingReferenceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('ADMIN') ?? false;
    }

    public function rules(): array
    {
        $route = $this->route();
        $routeName = $route instanceof Route ? (string) $route->getName() : '';
        $sorts = match (true) {
            str_contains($routeName, '.species.') => 'common_name,scientific_name,created_at',
            str_contains($routeName, '.sites.') => 'name,district,created_at',
            default => 'name,created_at',
        };

        return [
            'q' => ['nullable', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:'.$sorts],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}

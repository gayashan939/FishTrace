<?php

namespace App\Http\Requests\Files;

use App\Enums\FileCategory;
use App\Models\FileAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FileAsset::class) ?? false;
    }

    public function rules(): array
    {
        $category = FileCategory::tryFrom((string) $this->input('category'));
        $fileRule = $category?->isImage()
            ? File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->min(1)->max(5 * 1024)->dimensions(Rule::dimensions()->maxWidth(8000)->maxHeight(8000))
            : File::types(['pdf', 'jpg', 'jpeg', 'png'])->min(1)->max(10 * 1024);

        return [
            'category' => ['required', Rule::enum(FileCategory::class)->except([FileCategory::REPORT_EXPORT])],
            'entity_type' => ['required', Rule::in(['boat', 'catch_record', 'quality_inspection', 'processing_record', 'fish_batch', 'transport_trip', 'retail_receipt'])],
            'entity_id' => ['required', 'uuid'],
            'file' => ['required', $fileRule],
        ];
    }
}

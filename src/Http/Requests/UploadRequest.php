<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Asset\AssetsHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class UploadRequest extends FormRequest
{
    /** @return array<string, list<string|In>> */
    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1', 'max:'.AssetsHelper::getMaxAssetUploadSize()],
            'operation' => ['sometimes', Rule::in(['upload', 'replace'])],
            'folderId' => ['nullable', 'integer', 'min:1'],
            'fieldId' => ['nullable', 'integer', 'min:1'],
            'elementId' => ['nullable', 'integer', 'min:1'],
            'siteId' => ['nullable', 'integer', 'min:1'],
            'assetId' => ['nullable', 'integer', 'min:1'],
            'context' => ['sometimes', 'array', 'max:20'],
        ];
    }
}

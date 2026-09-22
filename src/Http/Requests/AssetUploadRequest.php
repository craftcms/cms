<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class AssetUploadRequest extends UploadRequest
{
    /** @return array<string, list<string|In>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
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

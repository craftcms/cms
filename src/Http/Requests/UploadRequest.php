<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @since 6.0.0
 */
class UploadRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
        ];
    }
}

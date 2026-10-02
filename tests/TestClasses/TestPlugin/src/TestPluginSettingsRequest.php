<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src;

use Illuminate\Foundation\Http\FormRequest;

class TestPluginSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'settings.foo' => ['required', 'in:via-form-request'],
        ];
    }
}

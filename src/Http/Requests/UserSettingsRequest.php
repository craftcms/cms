<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use Closure;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

class UserSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'photoVolumeUid' => ['nullable', 'uuid', Rule::in(Volumes::getAllVolumes()->pluck('uid'))],
            'photoSubpath' => ['nullable', 'string'],
            'require2fa' => $this->require2faRules(),
            'requireEmailVerification' => ['nullable', 'boolean'],
            'validateOnPublicRegistration' => ['nullable', 'boolean'],
            'allowPublicRegistration' => ['nullable', 'boolean'],
            'deactivateByDefault' => ['nullable', 'boolean'],
            'defaultGroup' => ['nullable', 'string'],
        ];
    }

    private function require2faRules(): array
    {
        return ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === false || $value === 'all' || $value === null) {
                return;
            }

            if ($value !== [] && is_array($value) && collect($value)->every(
                fn (mixed $value): bool => in_array($value, $this->require2faOptions(), true),
            )) {
                return;
            }

            $fail(t('Choose a valid two-step verification setting.'));
        }];
    }

    /** @return array<string> */
    private function require2faOptions(): array
    {
        return [
            'admins',
            ...UserGroups::getAllGroups()
                ->pluck('uid')
                ->all(),
        ];
    }
}

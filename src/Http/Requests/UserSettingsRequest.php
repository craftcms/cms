<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use Closure;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

class UserSettingsRequest extends FormRequest
{
    /** @return array<string, list<string|object>> */
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
            'defaultGroup' => ['nullable', 'uuid', Rule::in(UserGroups::getAllGroups()->pluck('uid'))],
        ];
    }

    /** @return array<string, bool|string|array<string>|null> */
    public function projectConfigSettings(): array
    {
        $data = $this->safe();

        return [
            'photoVolumeUid' => $data->input('photoVolumeUid'),
            'photoSubpath' => $data->input('photoSubpath'),
            ...(Edition::get()->supportsRequiring2FA() ? [
                'require2fa' => $data->input('require2fa', false),
            ] : []),
            ...(Edition::get()->supportsPublicRegistration() ? [
                'requireEmailVerification' => $data->boolean('requireEmailVerification'),
                'validateOnPublicRegistration' => $data->boolean('validateOnPublicRegistration'),
                'allowPublicRegistration' => $data->boolean('allowPublicRegistration'),
                'deactivateByDefault' => $data->boolean('deactivateByDefault'),
                'defaultGroup' => $data->input('defaultGroup'),
            ] : []),
        ];
    }

    /** @return list<string|Closure> */
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

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings\Users;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Requests\UserSettingsRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Data\UserSettings;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class UserSettingsController extends BaseUserSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly Volumes $volumes,
        private readonly UserGroups $userGroups,
    ) {
        parent::__construct();
    }

    public function index(): CpScreenResponse
    {
        $settings = new UserSettings($this->projectConfig->get('users') ?? []);

        return new CpScreenResponse()
            ->title(t('User Settings'))
            ->crumbs($this->crumbs(t('User Settings')))
            ->inertiaPage('settings/users/Settings', [
                'settings' => $settings,
                'canRequire2fa' => Edition::get()->supportsRequiring2FA(),
                'canManagePublicRegistration' => Edition::get()->supportsPublicRegistration(),
                'photoVolumeOptions' => $this->photoVolumeOptions(),
                'userGroupOptions' => $this->userGroupOptions(),
            ]);
    }

    public function store(UserSettingsRequest $request): Response
    {
        $projectConfigSettings = $this->projectConfig->get(ProjectConfig::PATH_USERS) ?? [];
        $settings = array_merge($projectConfigSettings, $this->settingsFromRequest($request->validated()));

        $this->projectConfig->set(
            path: ProjectConfig::PATH_USERS,
            value: $settings,
            message: 'Update user settings.',
        );

        return $this->asSuccess(t('User settings saved.'));
    }

    private function settingsFromRequest(array $data): array
    {
        return [
            'photoVolumeUid' => $data['photoVolumeUid'] ?? null,
            'photoSubpath' => $data['photoSubpath'] ?? null,
            ...(Edition::get()->supportsRequiring2FA() ? [
                'require2fa' => $data['require2fa'] ?? false,
            ] : []),
            ...(Edition::get()->supportsPublicRegistration() ? [
                'requireEmailVerification' => (bool) ($data['requireEmailVerification'] ?? false),
                'validateOnPublicRegistration' => (bool) ($data['validateOnPublicRegistration'] ?? false),
                'allowPublicRegistration' => (bool) ($data['allowPublicRegistration'] ?? false),
                'deactivateByDefault' => (bool) ($data['deactivateByDefault'] ?? false),
                'defaultGroup' => $data['defaultGroup'] ?? null,
            ] : []),
        ];
    }

    private function photoVolumeOptions(): Collection
    {
        return $this->volumes->getAllVolumes()
            ->map(fn (Volume $volume): array => [
                'label' => $volume->name,
                'value' => (string) $volume->uid,
            ])
            ->sortBy('label')
            ->values();

    private function userGroupOptions(): Collection
    {
        return $this->userGroups->getAllGroups()
            ->map(fn (UserGroup $group): array => [
                'label' => t($group->name ?? '', category: 'site'),
                'value' => (string) $group->uid,
            ]);
    }
}

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings\Users;

use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\Requests\UserSettingsRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\UserSettingsViewModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\User\Data\UserSettings;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class UserSettingsController extends BaseUserSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly Volumes $volumes,
        private readonly UserGroups $userGroups,
        private readonly FormResolver $formResolver,
        private readonly GeneralConfig $generalConfig,
    ) {}

    public function index(): CpScreenResponse
    {
        $settings = new UserSettings($this->projectConfig->get('users') ?? []);

        return new CpScreenResponse()
            ->title(t('User Settings'))
            ->crumbs($this->crumbs(t('User Settings')))
            ->inertiaPage('settings/users/Settings', [
                'subnav' => $this->subnav(),
                ...$this->viewModel($settings)->toArray(),
            ]);
    }

    public function renderForm(Request $request): JsonResponse
    {
        $request->validate([
            'values' => ['required', 'array'],
            'values.allowPublicRegistration' => ['required', 'boolean'],
            'scope' => ['present', 'array', 'size:0'],
        ]);

        return new JsonResponse([
            'form' => $this->viewModel(
                new UserSettings($this->projectConfig->get('users') ?? []),
                $request->array('values'),
            )->form(),
        ]);
    }

    public function store(UserSettingsRequest $request): Response
    {
        $projectConfigSettings = $this->projectConfig->get(ProjectConfig::PATH_USERS) ?? [];
        $settings = array_merge($projectConfigSettings, $request->projectConfigSettings());

        $this->projectConfig->set(
            path: ProjectConfig::PATH_USERS,
            value: $settings,
            message: 'Update user settings.',
        );

        return $this->asSuccess(t('User settings saved.'));
    }

    /** @param array<string, mixed>|null $values */
    private function viewModel(UserSettings $settings, ?array $values = null): UserSettingsViewModel
    {
        return new UserSettingsViewModel(
            $settings,
            $this->volumes,
            $this->userGroups,
            $this->formResolver,
            canRequire2fa: Edition::get()->supportsRequiring2FA(),
            canManagePublicRegistration: Edition::get()->supportsPublicRegistration(),
            readOnly: ! $this->generalConfig->allowAdminChanges,
            values: $values,
        );
    }
}

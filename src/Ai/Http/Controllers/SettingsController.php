<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Http\Controllers;

use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Ai\SettingsUi;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\UiContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class SettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private GeneralConfig $generalConfig,
        private ProjectConfig $projectConfig,
        private SettingsUi $settingsUi,
    ) {}

    public function index(Settings $settings): CpScreenResponse
    {
        return $this->screen($settings);
    }

    public function store(Request $request, Settings $settings): Response|CpScreenResponse
    {
        $submitted = $request->validate($settings->getRules());

        // Keep stored mappings the form doesn’t show, such as those of uninstalled plugins.
        Typecast::configure($settings, [
            'defaults' => array_replace($settings->defaults, $submitted['defaults'] ?? []),
            'tasks' => array_replace($settings->tasks, $submitted['tasks'] ?? []),
        ]);

        if (! $settings->validate()) {
            return $this->screen($settings);
        }

        $this->projectConfig->set(ProjectConfig::PATH_AI, $settings->toConfig(), 'Update AI settings.');

        return $this->asSuccess(t('AI settings saved.'));
    }

    private function screen(Settings $settings): CpScreenResponse
    {
        $mode = $this->generalConfig->allowAdminChanges
            ? ControlMode::Editable
            : ControlMode::ReadOnly;

        return new CpScreenResponse()
            ->title(t('AI Settings'))
            ->crumbs([
                new ActionItem()->label(t('Settings'))->href(route('craft.cp.settings.index')),
                new ActionItem()->label(t('AI')),
            ])
            ->redirectUrl('settings')
            ->ui($this->settingsUi->make($settings), new UiContext(
                values: $this->settingsUi->values($settings),
                errors: $settings->errors()->getMessages(),
                mode: $mode,
            ), props: [
                'readOnly' => ! $this->generalConfig->allowAdminChanges,
                'submit' => [
                    'method' => 'post',
                    'url' => route('craft.cp.settings.ai.store'),
                ],
            ]);
    }
}

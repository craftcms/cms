<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Http\Controllers;

use CraftCms\Cms\Ai\Ai;
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
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Http\JsonResponse;
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
        private Ai $ai,
        private GeneralConfig $generalConfig,
        private ProjectConfig $projectConfig,
        private SettingsUi $settingsUi,
        private UiResolver $uiResolver,
    ) {}

    public function index(Settings $settings): CpScreenResponse
    {
        return $this->screen($settings);
    }

    /**
     * Rebuilds the settings Ui for the submitted form values, so model options follow the chosen providers.
     */
    public function renderUi(Request $request, Settings $settings): JsonResponse
    {
        $request->validate([
            'values' => ['required', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);

        $values = $request->array('values');

        return new JsonResponse([
            'ui' => $this->uiResolver->resolve($this->settingsUi->make($settings, $values), new UiContext(
                values: $values,
                refreshable: true,
            )),
        ]);
    }

    public function store(Request $request, Settings $settings): Response|CpScreenResponse
    {
        $submitted = $request->validate($settings->getRules());
        $previousMappings = $this->ai->requirementMappings();

        // Keep stored mappings the form doesn’t show, such as those of uninstalled plugins.
        Typecast::configure($settings, [
            'defaults' => array_replace($settings->defaults, $submitted['defaults'] ?? []),
            'tasks' => array_replace($settings->tasks, $submitted['tasks'] ?? []),
        ]);

        if (! $settings->validate()) {
            return $this->screen($settings);
        }

        $this->projectConfig->set(ProjectConfig::PATH_AI, $settings->toConfig(), 'Update AI settings.');
        $this->ai->checkRequirements($previousMappings);

        return $this->asSuccess(t('AI settings saved.'));
    }

    private function screen(Settings $settings): CpScreenResponse
    {
        $mode = $this->generalConfig->allowAdminChanges
            ? ControlMode::Editable
            : ControlMode::ReadOnly;

        return new CpScreenResponse()
            ->title(t('AI Connections'))
            ->crumbs([
                new ActionItem()->label(t('Settings'))->href(route('craft.cp.settings.index')),
                new ActionItem()->label(t('AI Connections')),
            ])
            ->redirectUrl('settings')
            ->ui($this->settingsUi->make($settings), new UiContext(
                values: $this->settingsUi->values($settings),
                errors: $settings->errors()->getMessages(),
                mode: $mode,
                refreshable: $this->generalConfig->allowAdminChanges,
            ), props: [
                'readOnly' => ! $this->generalConfig->allowAdminChanges,
                'refreshUrl' => $this->generalConfig->allowAdminChanges ? route('craft.cp.settings.ai.render-ui') : null,
                'submit' => [
                    'method' => 'post',
                    'url' => route('craft.cp.settings.ai.store'),
                ],
            ]);
    }
}

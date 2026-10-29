<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\Mcp\SettingsUi;
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
        private GeneralConfig $generalConfig,
        private ProjectConfig $projectConfig,
        private SettingsUi $settingsUi,
        private UiResolver $uiResolver,
    ) {}

    public function index(Settings $settings): CpScreenResponse
    {
        return $this->screen($settings);
    }

    /** Rebuilds the settings Ui for the submitted form values, so its notices follow the endpoints. */
    public function renderUi(Request $request): JsonResponse
    {
        $request->validate([
            'values' => ['required', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);

        $values = $request->array('values');

        return new JsonResponse([
            'ui' => $this->uiResolver->resolve($this->settingsUi->make($values), new UiContext(
                values: $values,
                refreshable: true,
            )),
        ]);
    }

    public function store(Request $request, Settings $settings): Response|CpScreenResponse
    {
        Typecast::configure($settings, $request->validate($settings->getRules()));

        if (! $settings->validate()) {
            return $this->screen($settings);
        }

        $this->projectConfig->set('mcp', $settings->toArray(), 'Update MCP settings.');

        return $this->asSuccess(t('MCP settings saved.'));
    }

    private function screen(Settings $settings): CpScreenResponse
    {
        $editable = $this->generalConfig->allowAdminChanges;
        $values = $settings->toArray();

        return new CpScreenResponse()
            ->title(t('MCP Settings'))
            ->crumbs([
                new ActionItem()->label(t('Settings'))->href(route('craft.cp.settings.index')),
                new ActionItem()->label(t('MCP')),
            ])
            ->redirectUrl('settings')
            ->inertiaPage('settings/mcp/Settings', [
                'ui' => $this->uiResolver->resolve($this->settingsUi->make($values), new UiContext(
                    values: $values,
                    errors: $settings->errors()->getMessages(),
                    mode: $editable ? ControlMode::Editable : ControlMode::ReadOnly,
                    refreshable: $editable,
                )),
                'readOnly' => ! $editable,
                'refreshUrl' => $editable ? route('craft.cp.settings.mcp.render-ui') : null,
                'submit' => [
                    'method' => 'post',
                    'url' => route('craft.cp.settings.mcp.store'),
                ],
            ]);
    }
}

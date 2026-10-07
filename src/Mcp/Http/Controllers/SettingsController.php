<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\Mcp\SettingsForm;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Typecast;
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
        private FormResolver $formResolver,
        private ProjectConfig $projectConfig,
        private SettingsForm $settingsForm,
    ) {}

    public function index(Settings $settings): CpScreenResponse
    {
        return $this->screen($settings);
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
        $mode = $this->generalConfig->allowAdminChanges
            ? ControlMode::Editable
            : ControlMode::ReadOnly;
        $values = [
            ...$settings->toArray(),
            'endpoint' => route('craft.cp.mcp.server'),
        ];
        $form = $this->formResolver->resolve($this->settingsForm->make(), new FormContext(
            values: $values,
            errors: $settings->errors()->getMessages(),
            mode: $mode,
        ));

        return new CpScreenResponse()
            ->title(t('MCP Settings'))
            ->crumbs([
                new ActionItem()->label(t('Settings'))->href(route('craft.cp.settings.index')),
                new ActionItem()->label(t('MCP')),
            ])
            ->redirectUrl('settings')
            ->inertiaPage('Form', [
                'readOnly' => ! $this->generalConfig->allowAdminChanges,
                'form' => $form,
                'submit' => [
                    'method' => 'post',
                    'url' => route('craft.cp.settings.mcp.store'),
                ],
            ]);
    }
}

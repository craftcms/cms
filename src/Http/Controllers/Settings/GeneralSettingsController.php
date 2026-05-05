<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use CraftCms\Cms\Validation\Rules\TimezoneRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\t;

readonly class GeneralSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
    ) {}

    public function index(GeneralConfig $generalConfig): Response|View
    {
        $timezoneOptions = SelectOptions::getTimeZoneOptions();
        $timezoneOptions = array_merge($timezoneOptions, SelectOptions::getEnvOptions(array_column($timezoneOptions, 'value')));

        return Inertia::render('SettingsGeneralPage', [
            'system' => $this->projectConfig->get('system') ?? [],
            'nameSuggestions' => SelectOptions::getEnvSuggestions(),
            'timezoneOptions' => $timezoneOptions,
            'crumbs' => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('General Settings')],
            ],
            'systemStatusOptions' => SelectOptions::getBooleanEnvOptions(),
            'readOnly' => ! $generalConfig->allowAdminChanges,
            'saveUrl' => route('craft.cp.settings.general.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $settings = $request->validate([
            'name' => [new EnvValueRule(['required', 'string'])],
            'live' => [new EnvValueRule(['required', 'boolean'])],
            'retryDuration' => ['nullable', 'integer'],
            'timeZone' => [new EnvValueRule(['required', 'string', new TimezoneRule])],
        ]);

        $systemSettings = $this->projectConfig->get('system') ?? [];
        $systemSettings['name'] = $settings['name'];
        $systemSettings['live'] = $settings['live'];
        $systemSettings['retryDuration'] = $settings['retryDuration'] ?: null;
        $systemSettings['timeZone'] = $settings['timeZone'];

        $this->projectConfig->set('system', $systemSettings, 'Update system settings.');

        return back()->with('success', t('System settings saved.'));
    }
}

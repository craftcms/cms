<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use CraftCms\Cms\Validation\Rules\TimezoneRule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class GeneralSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
    ) {}

    public function index(): CpScreenResponse
    {
        $timezoneOptions = SelectOptions::getTimeZoneOptions();
        $timezoneOptions = array_merge($timezoneOptions, SelectOptions::getEnvOptions(array_column($timezoneOptions, 'value')));

        return new CpScreenResponse()
            ->title(t('General Settings'))
            ->crumbs([
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('General Settings')],
            ])
            ->redirectUrl('settings')
            ->inertiaPage('settings/General', [
                'system' => $this->projectConfig->get('system') ?? [],
                'nameSuggestions' => SelectOptions::getEnvSuggestions(),
                'timezoneOptions' => $timezoneOptions,
                'systemStatusOptions' => SelectOptions::getBooleanEnvOptions(),

            ]);
    }

    public function store(Request $request): Response
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
        $systemSettings['retryDuration'] = $settings['retryDuration'] ?? null;
        $systemSettings['timeZone'] = $settings['timeZone'];
        $this->projectConfig->set('system', $systemSettings, 'Update system settings.');

        return $this->asSuccess(t('System settings saved.'));
    }
}

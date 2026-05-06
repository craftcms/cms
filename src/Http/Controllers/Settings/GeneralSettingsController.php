<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Rules\TimezoneRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        return new CpScreenResponse()
            ->title(t('General Settings'))
            ->crumbs([
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('General Settings')],
            ])
            ->redirectUrl('settings')
            ->inertiaPage('SettingsGeneralPage', [
                'system' => $this->projectConfig->get('system') ?? [],
                'nameSuggestions' => SelectOptions::getEnvSuggestions(),
                'timezoneOptions' => [
                    ...SelectOptions::getTimeZoneOptions(),
                    ...SelectOptions::getEnvOptions(),
                ],
                'systemStatusOptions' => SelectOptions::getBooleanEnvOptions(),

            ]);
    }

    public function store(Request $request): Response
    {
        $resolvedValues = [];

        $envAllowedKeys = ['name', 'live', 'timeZone'];
        $booleans = ['live'];
        foreach ($request->all() as $key => $value) {
            if (in_array($key, $envAllowedKeys) && is_string($value) && str_starts_with($value, '$')) {
                $resolvedValues[$key] = in_array($key, $booleans) ? Env::parseBoolean($value) : Env::parse($value);
            } else {
                $resolvedValues[$key] = $value;
            }
        }

        /**
         * We want to validate against the resolved values, but we'll store what the user provided
         */
        Validator::make($resolvedValues, [
            'name' => ['required', 'string'],
            'live' => ['required', 'boolean'],
            'retryDuration' => ['nullable', 'integer'],
            'timeZone' => ['required', 'string', new TimezoneRule],
        ])->validate();

        $systemSettings = $this->projectConfig->get('system') ?? [];
        $systemSettings['name'] = $request->input('name');
        $systemSettings['live'] = $request->input('live');
        $systemSettings['retryDuration'] = $request->input('retryDuration') ?: null;
        $systemSettings['timeZone'] = $request->input('timeZone');

        $this->projectConfig->set('system', $systemSettings, 'Update system settings.');

        return $this->asSuccess(t('System settings saved.'));
    }
}

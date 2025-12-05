<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Rules\TimezoneRule;
use CraftCms\Cms\Support\Env;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

final readonly class GeneralSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
    ) {}

    public function index(GeneralConfig $generalConfig): Response|View
    {
        return Inertia::render('SettingsGeneralPage', [
            'system' => $this->projectConfig->get('system') ?? [],
            'nameOptions' => SelectOptions::getEnvSuggestions(),
            'timezoneOptions' => [
                ...SelectOptions::getTimeZoneOptions(),
                ...SelectOptions::getEnvOptions(),
            ],
            'systemStatusOptions' => SelectOptions::getBooleanEnvOptions(),
            'readOnly' => ! $generalConfig->allowAdminChanges,
            'saveUrl' => route('craft.cp.settings.general.store'),
        ]);
    }

    public function store(Request $request)
    {
        $resolvedValues = [];
        foreach ($request->all() as $key => $value) {
            if (is_string($value)) {
                $resolvedValues[$key] = str_starts_with($value, '$') ? Env::parse($value) : $value;
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

        $systemSettings = $this->projectConfig->get('system');
        $systemSettings['name'] = $request->input('name');
        $systemSettings['live'] = $request->input('live');
        $systemSettings['retryDuration'] = $request->input('retryDuration') ?: null;
        $systemSettings['timeZone'] = $request->input('timeZone');

        if (! str_starts_with((string) $systemSettings['live'], '$')) {
            $systemSettings['live'] = $request->boolean('live');
        }

        $this->projectConfig->set('system', $systemSettings, 'Update system settings.');

        return back()
            ->with('success', 'System settings saved.');
    }
}

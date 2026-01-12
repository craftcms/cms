<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\helpers\Assets;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Rebrand;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Rules\RequiresEditionRule;
use CraftCms\Cms\Shared\Rules\TimezoneRule;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\t;

final readonly class GeneralSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
        private Rebrand $rebrand,
    ) {}

    public function index(Request $request, GeneralConfig $generalConfig): Response|View
    {
        return Inertia::render('SettingsGeneralPage', [
            'system' => $this->projectConfig->get('system') ?? [],
            'nameSuggestions' => SelectOptions::getEnvSuggestions(),
            'timezoneOptions' => [
                ...SelectOptions::getTimeZoneOptions(),
                ...SelectOptions::getEnvOptions(),
            ],
            'crumbs' => [
                ['label' => t('Settings'), 'url' => UrlHelper::cpUrl('settings')],
                ['label' => t('General Settings')],
            ],
            'siteIcon' => $this->rebrand->getImage('icon') ? Arr::only($this->rebrand->getImage('icon'), ['url', 'name']) : null,
            'siteLogo' => $this->rebrand->getImage('logo') ? Arr::only($this->rebrand->getImage('logo'), ['url', 'name']) : null,
            'systemStatusOptions' => SelectOptions::getBooleanEnvOptions(),
            'readOnly' => ! $generalConfig->allowAdminChanges,
            'saveUrl' => route('craft.cp.settings.general.store'),
        ]);
    }

    public function store(Request $request)
    {
        $resolvedValues = [];

        $envAllowedKeys = ['name', 'live', 'timeZone'];
        foreach ($request->all() as $key => $value) {
            if (in_array($key, $envAllowedKeys) && is_string($value) && str_starts_with($value, '$')) {
                $resolvedValues[$key] = Env::parse($value);
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
            'siteIcon' => ['sometimes', 'nullable', new RequiresEditionRule(Edition::Pro), 'image:allow_svg'],
            'siteLogo' => ['sometimes', 'nullable', new RequiresEditionRule(Edition::Pro), 'image:allow_svg'],
        ])->validate();

        if (Edition::isAtLeast(Edition::Pro)) {
            $this->updateRebrand($request);
        }

        $systemSettings = $this->projectConfig->get('system') ?? [];
        $systemSettings['name'] = $request->input('name');
        $systemSettings['live'] = $request->input('live');
        $systemSettings['retryDuration'] = $request->input('retryDuration') ?: null;
        $systemSettings['timeZone'] = $request->input('timeZone');

        if (! str_starts_with((string) $systemSettings['live'], '$')) {
            $systemSettings['live'] = $request->boolean('live');
        }

        $this->projectConfig->set('system', $systemSettings, 'Update system settings.');

        return back()
            ->with('success', t('System settings saved.'));
    }

    /**
     * @TODO Refactor/remove this once rebrand assets are refactored
     */
    private function updateRebrand(Request $request): void
    {
        foreach (['siteIcon', 'siteLogo'] as $key) {
            // Resolve the input name to the folder
            $folder = $key === 'siteIcon' ? 'icon' : 'logo';

            // Make sure the user actually wants to change this
            if (! $request->has($key)) {
                continue;
            }

            // If the user explicitly removed the file just delete the folder
            if ($request->input($key) === null) {
                Storage::disk('rebrand')->deleteDirectory($folder);
            }

            // If the user uploaded a new file
            if (! $request->hasFile($key)) {
                continue;
            }

            $image = $request->file($key);
            Storage::disk('rebrand')->deleteDirectory($folder);
            if ($image) {
                $safeName = Assets::prepareAssetName($image->getClientOriginalName(), true, true);
                $image->storePubliclyAs($folder, $safeName, 'rebrand');
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\web\assets\generalsettings\GeneralSettingsAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\DateTime;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;

final readonly class GeneralSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
    ) {}

    public function index(GeneralConfig $generalConfig): \Inertia\Response|View
    {
        if (request()->has('legacy')) {
            \Craft::$app->getView()->registerAssetBundle(GeneralSettingsAsset::class);

            return view('craftcms::settings/general/_index', [
                'system' => $this->projectConfig->get('system') ?? [],
                'readOnly' => ! $generalConfig->allowAdminChanges,
            ]);
        }

        return Inertia::render('Settings/General/Index', [
            'system' => $this->projectConfig->get('system') ?? [],
            'timezones' => DateTime::getTimeZoneOptions(),
            'readOnly' => ! $generalConfig->allowAdminChanges,
            'saveUrl' => route('craft.cp.settings.general.store'),
        ]);
    }

    public function store(Request $request)
    {
        $systemSettings = $this->projectConfig->get('system');
        $systemSettings['name'] = $request->input('name');
        $systemSettings['live'] = $request->input('live');
        $systemSettings['retryDuration'] = $request->input('retryDuration') ?: null;
        $systemSettings['timeZone'] = $request->input('timeZone');

        if (! str_starts_with((string) $systemSettings['live'], '$')) {
            $systemSettings['live'] = (bool) $systemSettings['live'];
        }

        $this->projectConfig->set('system', $systemSettings, 'Update system settings.');

        $request->session()->flash('message', 'System settings saved.');

        return back()
            ->with('success', 'System settings saved.');
    }
}

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\web\assets\generalsettings\GeneralSettingsAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class GeneralSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private ProjectConfig $projectConfig,
    ) {}

    public function index(GeneralConfig $generalConfig): View
    {
        \Craft::$app->getView()->registerAssetBundle(GeneralSettingsAsset::class);

        return view('craftcms::settings/general/_index', [
            'system' => $this->projectConfig->get('system') ?? [],
            'readOnly' => ! $generalConfig->allowAdminChanges,
        ]);
    }

    public function store(Request $request): Response
    {
        $systemSettings = $this->projectConfig->get('system');
        $systemSettings['name'] = $request->get('name');
        $systemSettings['live'] = $request->get('live');
        $systemSettings['retryDuration'] = $request->get('retryDuration') ?: null;
        $systemSettings['timeZone'] = $request->get('timeZone');

        if (! str_starts_with((string) $systemSettings['live'], '$')) {
            $systemSettings['live'] = (bool) $systemSettings['live'];
        }

        $this->projectConfig->set('system', $systemSettings, 'Update system settings.');

        return $this->asSuccess(t('General settings saved.'));
    }
}

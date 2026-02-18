<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use Craft;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Flash;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class UserSettingsController
{
    public function __construct(
        private ProjectConfig $projectConfig,
        private GeneralConfig $generalConfig,
    ) {}

    public function index(): View
    {
        return view('settings/users/_settings', [
            'readOnly' => ! $this->generalConfig->allowAdminChanges,
            'settings' => $this->projectConfig->get('users') ?? [],
        ]);
    }

    public function store(Request $request): Response
    {
        $request->validate([
            'photoVolumeId' => ['nullable', 'integer'],
            'photoSubpath' => ['nullable', 'string'],
            'require2fa' => ['nullable'],
            'requireEmailVerification' => ['nullable', 'boolean'],
            'validateOnPublicRegistration' => ['nullable', 'boolean'],
            'allowPublicRegistration' => ['nullable', 'boolean'],
            'deactivateByDefault' => ['nullable', 'boolean'],
            'defaultGroup' => ['nullable', 'string'],
        ]);

        $settings = $this->projectConfig->get('users') ?? [];
        $settings['photoVolumeUid'] = $request->input('photoVolumeId')
            ? Craft::$app->getVolumes()->getVolumeById($request->integer('photoVolumeId'))?->uid
            : null;
        $settings['photoSubpath'] = $request->input('photoSubpath');

        if (Edition::get()->value >= Edition::Team->value) {
            $settings['require2fa'] = $request->boolean('require2fa');
        }

        if (Edition::get()->value >= Edition::Pro->value) {
            $settings['requireEmailVerification'] = $request->boolean('requireEmailVerification');
            $settings['validateOnPublicRegistration'] = $request->boolean('validateOnPublicRegistration');
            $settings['allowPublicRegistration'] = $request->boolean('allowPublicRegistration');
            $settings['deactivateByDefault'] = $request->boolean('deactivateByDefault');
            $settings['defaultGroup'] = $request->input('defaultGroup');
            $settings['require2fa'] = $request->input('require2fa');
        }

        $this->projectConfig->set('users', $settings, 'Update user settings.');

        Flash::success(t('User settings saved.'));

        return back();
    }
}

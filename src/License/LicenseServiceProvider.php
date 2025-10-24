<?php

declare(strict_types=1);

namespace CraftCms\Cms\License;

use craft\helpers\App;
use CraftCms\Cms\Support\Env;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class LicenseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->ensureLicenseExists();
    }

    private function ensureLicenseExists(): void
    {
        if (Env::get('CRAFT_LICENSE_KEY')) {
            return;
        }

        if (App::isEphemeral()) {
            return;
        }

        $licenseKeyPath = Env::get('CRAFT_LICENSE_KEY_PATH');

        if ($licenseKeyPath) {
            $licensePath = dirname($licenseKeyPath);
            $licenseKeyName = basename($licenseKeyPath);
        } else {
            $licensePath = $this->app->configPath('craft');
            $licenseKeyName = 'license.key';
        }

        // Make sure the license folder exists.
        File::ensureDirectoryExists($licensePath);

        if ($this->app->runningInConsole()) {
            return;
        }

        $licenseFullPath = "$licensePath/$licenseKeyName";

        if (File::exists($licenseFullPath)) {
            return;
        }

        if (File::put($licenseFullPath, 'temp') === false || File::get($licenseFullPath) !== 'temp') {
            abort(503, $licensePath.' isn\'t writable by PHP. Please fix that.');
        }
    }
}

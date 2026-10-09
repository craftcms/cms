<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Testing;

use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Json;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\package_path;

/** @mixin TestCase */
trait InstallsPlugin
{
    public function setupInstallsPlugin(): void
    {
        $this->composerInstallPlugin();
        $this->installAndEnablePlugin();
        $this->app->get(Plugins::class)->loadPlugins();
    }

    public function tearDownInstallsPlugin(): void
    {
        if (getenv('TEST_TOKEN') !== false) {
            return;
        }

        File::delete($this->app->basePath('vendor/craftcms/plugins.php'));
    }

    public function composerInstallPlugin(): void
    {
        $composer = $this->getComposerInfo();

        $plugins = var_export([
            $composer['name'] => array_merge($composer['extra'], [
                'basePath' => package_path('/src'),
            ]),
        ], true);

        File::ensureDirectoryExists($this->app->basePath('vendor/craftcms'));

        $path = $this->app->basePath('vendor/craftcms/plugins.php');
        $tempPath = sprintf('%s.%s.tmp', $path, bin2hex(random_bytes(8)));

        File::put($tempPath, "<?php return $plugins;\n");
        File::move($tempPath, $path);

        $this->app->forgetInstance(Plugins::class);
    }

    public function installAndEnablePlugin(): void
    {
        $plugins = $this->app->get(Plugins::class);
        $composer = $this->getComposerInfo();

        $plugins->installPlugin($composer['extra']['handle']);
        $plugins->enablePlugin($composer['extra']['handle']);

        $this->app->forgetInstance(Plugins::class);
    }

    /** @return array{name: string, extra: array{handle: string}} */
    private function getComposerInfo(): array
    {
        return once(fn () => Json::decode(File::get(package_path('composer.json'))));
    }
}

<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Testing;

use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\package_path;

/** @mixin TestCase */
trait InstallsPlugin
{
    public function setupInstallsPlugin(): void
    {
        $this->composerInstallPlugin();
        $this->installAndEnablePlugin();
        $this->bootPluginProvider();
    }

    public function tearDownInstallsPlugin(): void
    {
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
        File::put($this->app->basePath('vendor/craftcms/plugins.php'), "<?php return $plugins;\n");

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

    public function bootPluginProvider(): void
    {
        $plugins = $this->app->get(Plugins::class);
        $plugins->loadPlugins();

        $composer = $this->getComposerInfo();

        $this->app->make($composer['extra']['class'], ['app' => app()])->boot($plugins);
    }

    private function getComposerInfo(): array
    {
        return once(fn () => Json::decode(File::get(package_path('composer.json'))));
    }
}

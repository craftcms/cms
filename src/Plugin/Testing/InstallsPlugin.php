<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Testing;

use Composer\InstalledVersions;
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

        $routes = $this->app->get('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    public function composerInstallPlugin(): void
    {
        $composer = $this->getComposerInfo();

        $this->app->when(Plugins::class)
            ->needs('$composerPlugins')
            ->give(fn () => [
                $composer['name'] => array_merge($composer['extra'], [
                    'basePath' => package_path('/src'),
                    'version' => $composer['version'] ?? InstalledVersions::getRootPackage()['pretty_version'],
                ]),
            ]);

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

    /** @return array{name: string, version?: string, extra: array{handle: string}} */
    private function getComposerInfo(): array
    {
        return once(fn () => Json::decode(File::get(package_path('composer.json'))));
    }
}

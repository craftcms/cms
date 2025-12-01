<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Support\Json;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

/**
 * @internal
 */
final class PluginPackageManifest extends PackageManifest
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function build(): void
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
            $installed = Json::decode($this->files->get($path));

            $packages = $installed['packages'] ?? $installed;
        }

        $ignoreAll = in_array('*', $ignore = $this->packagesToIgnore());

        $this->write(new Collection($packages)->mapWithKeys(function ($package) {
            $laravelConfig = $package['extra']['laravel'] ?? [];

            if (! $laravelConfig && $package['type'] === 'craft-plugin') {
                $pluginClass = $this->determinePluginClass($package);

                if (is_subclass_of($pluginClass, ServiceProvider::class)) {
                    return [$this->format($package['name']) => [
                        'providers' => [$pluginClass],
                    ]];
                }
            }

            return [$this->format($package['name']) => $laravelConfig];
        })->each(function ($configuration) use (&$ignore) {
            $ignore = array_merge($ignore, $configuration['dont-discover'] ?? []);
        })->reject(fn ($configuration, $package) => $ignoreAll || in_array($package, $ignore))->filter()->all());
    }

    private function determinePluginClass(array $package): ?string
    {
        if (isset($package['extra']['class'])) {
            return $package['extra']['class'];
        }

        foreach ($package['autoload']['psr-4'] ?? [] as $namespace => $path) {
            if (is_array($path)) {
                // Don't support aliases that point to multiple base paths
                continue;
            }

            $path = base_path('vendor/'.$package['name'].'/'.$path);

            // If we're still looking for the primary Plugin class, see if it's in here
            if (file_exists($path.'/Plugin.php')) {
                return $namespace.'Plugin';
            }
        }

        return null;
    }
}

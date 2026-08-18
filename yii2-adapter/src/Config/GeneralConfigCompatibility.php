<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Config;

use craft\config\GeneralConfig as LegacyGeneralConfig;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Typecast;

readonly class GeneralConfigCompatibility
{
    public function convert(mixed $config, mixed $overlay = null): GeneralConfig|array
    {
        $config = $this->resolve($config, LegacyGeneralConfig::create());

        if ($overlay === null || $overlay === []) {
            return $config;
        }

        Deprecator::log('config-general-app-type', 'Using general.web.php and general.console.php configuration files is deprecated.');

        $overlay = $this->resolve($overlay, $config instanceof GeneralConfig ? $config : LegacyGeneralConfig::create());

        if ($overlay instanceof GeneralConfig) {
            return $overlay;
        }

        if (is_array($config)) {
            return array_merge($config, $overlay);
        }

        Typecast::properties($config::class, $overlay);

        foreach ($overlay as $setting => $value) {
            method_exists($config, $setting)
                ? $config->{$setting}($value)
                : $config->{$setting} = $value;
        }

        return $config;
    }

    private function resolve(mixed $config, GeneralConfig $default): GeneralConfig|array
    {
        if (is_callable($config)) {
            Deprecator::log('config-general-callable', 'Returning a callable from general.php is deprecated.');
            $config = $config($default);
        }

        if ($config instanceof LegacyGeneralConfig) {
            $this->registerAliases($config->aliases);

            return $config;
        }

        if (is_array($config)) {
            $aliases = array_merge(
                $config['environmentVariables'] ?? [],
                $config['aliases'] ?? [],
            );
            unset($config['environmentVariables'], $config['aliases']);
            $this->registerAliases($aliases);

            return $config;
        }

        if ($config instanceof GeneralConfig) {
            return $config;
        }

        return [];
    }

    /** @param array<string, string|null> $aliases */
    private function registerAliases(array $aliases): void
    {
        foreach ($aliases as $name => $path) {
            $path === null
                ? Aliases::remove($name)
                : Aliases::set($name, $path);
        }
    }
}

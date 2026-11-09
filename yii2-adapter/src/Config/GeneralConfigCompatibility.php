<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Config;

use craft\config\GeneralConfig as LegacyGeneralConfig;
use CraftCms\Cms\Config\AnchorsConfig;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;

readonly class GeneralConfigCompatibility
{
    /**
     * Converts the general config, with the general.web.php or general.console.php overlay for the application type.
     */
    public function register(Application $app): void
    {
        $appType = $app->runningInConsole() ? 'console' : 'web';
        $config = Config::get('craft.general', []);
        $overlay = Config::get("craft.general.$appType");

        // Laravel loads the overlays into `craft.general.web` and `craft.general.console`, which replaces a
        // general.php object or callable with an array, so the files are read again
        if (!$app->configurationIsCached() && (is_file(config_path('craft/general.web.php')) || is_file(config_path('craft/general.console.php')))) {
            $config = $this->requireConfigFile($app, 'general') ?? [];
            $overlay = $this->requireConfigFile($app, "general.$appType");
        }

        Config::set('craft.general', $this->applyAnchorsConfig(
            $this->convert($config, $overlay),
            Config::get('craft.anchors'),
        ));
    }

    /**
     * Converts a Craft 5 general config to a config object.
     *
     * An array config becomes a legacy config object, so settings removed from
     * core still reach their shims, the same as when they're set fluently.
     */
    public function convert(mixed $config, mixed $overlay = null): GeneralConfig
    {
        $config = $this->resolve($config, LegacyGeneralConfig::create());

        if ($overlay === null || $overlay === []) {
            return $this->toConfigObject($config);
        }

        Deprecator::log('config-general-app-type', 'Using general.web.php and general.console.php configuration files is deprecated.');

        // Merge array overlays first, so overridden settings never reach setters with side effects
        if (is_array($config) && is_array($overlay) && !is_callable($overlay)) {
            return $this->toConfigObject(array_merge($config, $overlay));
        }

        // An object overlay replaces the config, as it did in Craft 5. It's returned before an array config is converted,
        // so the replaced settings never reach setters with side effects.
        if ($overlay instanceof GeneralConfig) {
            return $overlay;
        }

        $object = $this->toConfigObject($config);
        $overlay = $this->resolve($overlay, $object);

        if ($overlay instanceof GeneralConfig) {
            return $overlay;
        }

        if (is_array($config)) {
            return $this->toConfigObject(array_merge($config, $overlay));
        }

        $this->applySettings($object, $overlay);

        return $object;
    }

    private function requireConfigFile(Application $app, string $name): mixed
    {
        $path = config_path("craft/$name.php");

        if (!is_file($path)) {
            return null;
        }

        $config = require $path;

        return is_array($config) && array_key_exists('*', $config)
            ? Arr::merge($config['*'], $config[$app->environment()] ?? [])
            : $config;
    }

    /**
     * Applies a `config/craft/anchors.php` file left over from the Anchors plugin, unless the general config sets `anchors`.
     */
    public function applyAnchorsConfig(GeneralConfig $config, mixed $anchors): GeneralConfig
    {
        if (!is_array($anchors) || $anchors === []) {
            return $config;
        }

        Deprecator::log('config-anchors', 'The `config/craft/anchors.php` config file is deprecated. Move its settings to the `anchors` setting in `config/craft/general.php`.');

        if ($config->anchors == AnchorsConfig::create()) {
            $config->anchors($anchors);
        }

        return $config;
    }

    private function resolve(mixed $config, GeneralConfig $default): GeneralConfig|array
    {
        if (is_callable($config)) {
            Deprecator::log('config-general-callable', 'Returning a callable from general.php is deprecated.');
            $config = $config($default);
        }

        if ($config instanceof GeneralConfig || is_array($config)) {
            return $config;
        }

        return [];
    }

    /**
     * @param GeneralConfig|array<string, mixed> $config
     */
    private function toConfigObject(GeneralConfig|array $config): GeneralConfig
    {
        if ($config instanceof GeneralConfig) {
            return $config;
        }

        $object = LegacyGeneralConfig::create();
        $this->applySettings($object, $config);

        return $object;
    }

    /**
     * Applies settings the way core applies an array config, through the fluent methods when they exist.
     *
     * @param array<string, mixed> $settings
     */
    private function applySettings(GeneralConfig $config, array $settings): void
    {
        Typecast::properties($config::class, $settings);

        foreach ($settings as $setting => $value) {
            method_exists($config, $setting)
                ? $config->{$setting}($value)
                : $config->{$setting} = $value;
        }
    }
}

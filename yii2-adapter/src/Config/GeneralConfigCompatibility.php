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
     * Replaces `craft.general` with a config object, applying the application type overlay and any leftover `anchors.php` file.
     */
    public function register(Application $app): void
    {
        [$config, $overlay] = $this->loadConfig($app, $app->runningInConsole() ? 'console' : 'web');

        Config::set('craft.general', $this->applyAnchorsConfig(
            $this->convert($config, $overlay),
            Config::get('craft.anchors'),
        ));
    }

    /**
     * Returns the `general.php` config and its application type overlay.
     *
     * Laravel's config loader stores `general.web.php` and `general.console.php` inside `craft.general`,
     * replacing a `general.php` that returns a config object or a callable, so the file is loaded again.
     * A config object overlay already applied its settings when it loaded, so it loads again after the base,
     * and settings with side effects, such as `enableCsrfProtection`, still end on the overlay's value.
     *
     * @return array{mixed, mixed}
     */
    private function loadConfig(Application $app, string $appType): array
    {
        $config = Config::get('craft.general', []);
        $overlay = Config::get("craft.general.{$appType}");
        $path = $app->configPath('craft/general.php');

        if (!is_array($config) || !Arr::hasAny($config, ['web', 'console'])) {
            return [$config, $overlay];
        }

        if (!is_file($path)) {
            return [Arr::except($config, ['web', 'console']), $overlay];
        }

        $config = $this->resolve((static fn() => require $path)(), LegacyGeneralConfig::create());
        $overlayPath = $app->configPath("craft/general.{$appType}.php");

        if ($overlay instanceof GeneralConfig && is_file($overlayPath)) {
            $overlay = (static fn() => require $overlayPath)();
        }

        return [$config, $overlay];
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

        if (is_array($config)) {
            $overlay = $this->resolve($overlay, LegacyGeneralConfig::create());

            // Merge array overlays first, so overridden settings never reach setters with side effects
            return is_array($overlay) ? $this->toConfigObject(array_merge($config, $overlay)) : $overlay;
        }

        $overlay = $this->resolve($overlay, $config);

        if ($overlay instanceof GeneralConfig) {
            return $overlay;
        }

        $this->applySettings($config, $overlay);

        return $config;
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

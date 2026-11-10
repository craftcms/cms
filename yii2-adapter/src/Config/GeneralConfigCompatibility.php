<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Config;

use craft\config\GeneralConfig as LegacyGeneralConfig;
use CraftCms\Cms\Config\AnchorsConfig;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Arr;
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
     * replacing a `general.php` that returns a config object or a callable, so the file is loaded again to recover it.
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

        $base = is_file($path) ? (static fn() => require $path)() : [];

        // An array base is still in the config, already normalized from any multi-environment array
        if (is_array($base)) {
            return [Arr::except($config, ['web', 'console']), $overlay];
        }

        // A callable base is left for convert(), which holds back its side effects, or skips it when the overlay replaces it
        $config = $base;
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
        if ($overlay === null || $overlay === []) {
            return $this->toConfigObject($this->resolve($config, LegacyGeneralConfig::create()));
        }

        // An object overlay replaces the config, as it did in Craft 5, so the config is never resolved or converted
        if ($overlay instanceof GeneralConfig) {
            return $overlay;
        }

        // Changes the config and a callable overlay make to Laravel’s state are held back on the configs they're made on,
        // and only those of the config that's kept are applied, so settings that are replaced, merged away, or set on a
        // discarded config never reach setters with side effects. A clone keeps the changes made on its original.
        $config = LegacyGeneralConfig::holdSideEffects(fn() => $this->resolve($config, LegacyGeneralConfig::create()));

        if (is_array($config)) {
            if (is_array($overlay) && !is_callable($overlay)) {
                return $this->toConfigObject(array_merge($config, $overlay));
            }

            $object = LegacyGeneralConfig::holdSideEffects(fn() => $this->toConfigObject($config));
        } else {
            $object = $config;
        }

        $overlay = LegacyGeneralConfig::holdSideEffects(fn() => $this->resolve($overlay, $object));

        if ($overlay !== $object && $overlay instanceof GeneralConfig) {
            $this->applyHeldSideEffects($overlay);

            return $overlay;
        }

        if (is_array($config) && is_array($overlay)) {
            return $this->toConfigObject(array_merge($config, $overlay));
        }

        $this->applyHeldSideEffects($object);

        if (is_array($overlay)) {
            $this->applySettings($object, $overlay);
        }

        return $object;
    }

    private function applyHeldSideEffects(GeneralConfig $config): void
    {
        if ($config instanceof LegacyGeneralConfig) {
            $config->applyHeldSideEffects();
        }
    }

    /**
     * Applies a `config/craft/anchors.php` file left over from the Anchors plugin, unless the general config sets `anchors`.
     */
    public function applyAnchorsConfig(GeneralConfig $config, mixed $anchors): GeneralConfig
    {
        if (!is_array($anchors) || $anchors === []) {
            return $config;
        }

        if ($config->anchors == AnchorsConfig::create()) {
            $config->anchors($anchors);
        }

        return $config;
    }

    private function resolve(mixed $config, GeneralConfig $default): GeneralConfig|array
    {
        if (is_callable($config)) {
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

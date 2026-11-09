<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Config;

use craft\config\GeneralConfig as LegacyGeneralConfig;
use CraftCms\Cms\Config\AnchorsConfig;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Typecast;

readonly class GeneralConfigCompatibility
{
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

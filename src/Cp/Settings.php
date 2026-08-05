<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Url;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Builds the control panel settings navigation and registers additional links.
 *
 * ```php
 * public function boot(Settings $settings): void
 * {
 *     $settings->registerSetting('My Plugin', 'general', fn () => [
 *         'label' => 'General',
 *         'url' => route('my-plugin.settings'),
 *     ]);
 * }
 * ```
 *
 * Use {@see registerReadOnlySetting()} for links shown when admin changes are disabled.
 */
#[Singleton]
class Settings
{
    /** @var array<string, array<string, Closure(): array<string, mixed>>> */
    private array $providers = [];

    /** @var array<string, array<string, Closure(): array<string, mixed>>> */
    private array $readonlyProviders = [];

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly Plugins $pluginsService,
    ) {}

    /** @param Closure(): array{label:string, url?:string, icon?:string, iconName?:string} $provider */
    public function registerSetting(string $section, string $handle, Closure $provider): void
    {
        $this->registerProvider($this->providers, $section, $handle, $provider);
    }

    /** @param Closure(): array{label:string, url?:string, icon?:string, iconName?:string} $provider */
    public function registerReadOnlySetting(string $section, string $handle, Closure $provider): void
    {
        $this->registerProvider($this->readonlyProviders, $section, $handle, $provider);
    }

    public function remove(string $section, string ...$handles): void
    {
        foreach ($handles as $handle) {
            unset($this->providers[$section][$handle], $this->readonlyProviders[$section][$handle]);
        }
    }

    /** @return array<string, array<string, array<string, mixed>>> */
    public function all(): array
    {
        $readOnly = ! $this->generalConfig->allowAdminChanges;
        $settings = [];

        $label = t('System');

        $settings[$label]['general'] = [
            'url' => route('craft.cp.settings.general.index'),
            'iconName' => 'light/sliders',
            'label' => t('General'),
        ];

        $settings[$label]['sites'] = [
            'iconName' => sprintf('light/%s', Icons::earth()),
            'label' => t('Sites'),
        ];

        if (! $this->generalConfig->headlessMode) {
            $settings[$label]['routes'] = [
                'iconName' => 'light/signs-post',
                'label' => t('Routes'),
            ];
        }

        $settings[$label]['users'] = [
            'iconName' => 'light/user-group',
            'label' => t('Users'),
        ];

        if ($this->generalConfig->allowAdminChanges) {
            $settings[$label]['addresses'] = [
                'iconName' => 'light/map-location',
                'label' => t('Addresses'),
            ];

            $settings[$label]['email'] = [
                'url' => route('craft.cp.settings.email.index'),
                'iconName' => 'light/envelope',
                'label' => t('Email'),
            ];
        }

        $settings[$label]['plugins'] = [
            'iconName' => 'light/plug',
            'label' => t('Plugins'),
        ];

        $label = t('Content');

        $settings[$label]['sections'] = [
            'iconName' => 'light/newspaper',
            'label' => t('Sections'),
        ];
        $settings[$label]['entry-types'] = [
            'iconName' => 'light/files',
            'label' => t('Entry Types'),
        ];
        $settings[$label]['fields'] = [
            'iconName' => 'light/pen-to-square',
            'label' => t('Fields'),
        ];

        $label = t('Media');

        $settings[$label]['assets'] = [
            'iconName' => 'light/image',
            'label' => t('Assets'),
        ];
        $settings[$label]['filesystems'] = [
            'iconName' => 'light/folder-open',
            'label' => t('Filesystems'),
        ];

        $label = t('Plugins');

        foreach ($this->pluginsService->getAllPlugins() as $plugin) {
            if ($plugin->hasCpSettings && (! $readOnly || $plugin->hasReadOnlyCpSettings)) {
                $settings[$label][$plugin->handle] = [
                    'url' => Url::cpUrl('settings/plugins/'.$plugin->handle),
                    'icon' => $this->pluginsService->getPluginIconSvg($plugin->handle),
                    'label' => $plugin->name,
                ];
            }
        }

        return $this->apply($settings, $readOnly);
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $settings
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function apply(array $settings, bool $readOnly): array
    {
        foreach ($readOnly ? $this->readonlyProviders : $this->providers as $section => $providers) {
            $section = t($section);

            foreach ($providers as $handle => $provider) {
                $setting = app()->call($provider);

                if (! is_array($setting) ||
                    ! isset($setting['label']) ||
                    ! is_string($setting['label']) ||
                    array_any(['url', 'icon', 'iconName'], fn (string $key) => array_key_exists($key, $setting) && ! is_string($setting[$key]))
                ) {
                    throw new InvalidArgumentException("Invalid CP setting [$section.$handle].");
                }

                if (isset($settings[$section][$handle])) {
                    throw new InvalidArgumentException("CP setting [$section.$handle] is already registered.");
                }

                $settings[$section][$handle] = $setting;
            }
        }

        return $settings;
    }

    /**
     * @param  array<string, array<string, Closure(): array<string, mixed>>>  $providers
     * @param  Closure(): array{label:string, url?:string, icon?:string, iconName?:string}  $provider
     */
    private function registerProvider(array &$providers, string $section, string $handle, Closure $provider): void
    {
        if ($section === '') {
            throw new InvalidArgumentException('CP settings sections cannot be empty.');
        }

        if ($handle === '') {
            throw new InvalidArgumentException('CP setting handles cannot be empty.');
        }

        if (isset($providers[$section][$handle])) {
            throw new InvalidArgumentException("CP setting [$section.$handle] is already registered.");
        }

        $providers[$section][$handle] = $provider;
    }
}

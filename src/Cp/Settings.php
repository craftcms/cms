<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Facades\I18N;
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
 *         'iconName' => 'gear',
 *     ]);
 * }
 * ```
 *
 * An `iconName` is a Font Awesome icon name. The settings index draws its light
 * style where Craft ships one, and the nav draws its solid style.
 *
 * Use {@see registerReadOnlySetting()} for links shown when admin changes are disabled.
 *
 * @since 6.0.0
 */
#[Singleton]
class Settings
{
    /** @var array<string, array<string, Closure(): array<string, mixed>>> */
    private array $providers = [];

    /** @var array<string, array<string, Closure(): array<string, mixed>>> */
    private array $readonlyProviders = [];

    /** @var array<string, string> Each section's untranslated name, by its label. */
    private array $sectionNames = [];

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

        $label = $this->sectionLabel('System');

        $settings[$label]['general'] = [
            'url' => route('craft.cp.settings.general.index'),
            'iconName' => 'sliders',
            'label' => t('General'),
        ];

        $settings[$label]['sites'] = [
            'iconName' => Icons::earth(),
            'label' => t('Sites'),
        ];

        if (! $this->generalConfig->headlessMode) {
            $settings[$label]['routes'] = [
                'iconName' => 'signs-post',
                'label' => t('Routes'),
            ];
        }

        $settings[$label]['users'] = [
            'iconName' => 'user-group',
            'label' => t('Users'),
        ];

        $settings[$label]['cp-nav'] = [
            'url' => route('craft.cp.settings.cp-nav.index'),
            'iconName' => I18N::getLocale()->getOrientation() === 'rtl'
                ? 'custom-icons/sidebar-nav-flip-light'
                : 'custom-icons/sidebar-nav-light',
            'label' => t('CP Navigation'),
        ];

        if ($this->generalConfig->allowAdminChanges) {
            $settings[$label]['addresses'] = [
                'iconName' => 'map-location',
                'label' => t('Addresses'),
            ];

            $settings[$label]['email'] = [
                'url' => route('craft.cp.settings.email.index'),
                'iconName' => 'envelope',
                'label' => t('Email'),
            ];
        }

        $label = $this->sectionLabel('Content');

        $settings[$label]['sections'] = [
            'iconName' => 'newspaper',
            'label' => t('Sections'),
        ];
        $settings[$label]['entry-types'] = [
            'iconName' => 'files',
            'label' => t('Entry Types'),
        ];

        if (Edition::isAtLeast(Edition::Pro)) {
            $settings[$label]['workflows'] = [
                'iconName' => 'clipboard-list-check',
                'label' => t('Workflows'),
            ];
        }

        $settings[$label]['assets'] = [
            'iconName' => 'image',
            'label' => t('Assets'),
        ];

        $settings[$label]['fields'] = [
            'iconName' => 'pen-to-square',
            'label' => t('Fields'),
        ];

        $label = $this->sectionLabel('Extend');

        $settings[$label]['plugins'] = [
            'iconName' => 'plug',
            'label' => t('Plugins'),
        ];

        $settings[$label]['connections'] = [
            'url' => route('craft.cp.settings.ai.index'),
            'iconName' => 'sparkles',
            'label' => t('Connections'),
        ];

        // Reserved even if no plugin has settings yet, so sections that plugins register come after it
        $label = $this->sectionLabel('Plugins');
        $settings[$label] = [];

        foreach ($this->pluginsService->getAllPlugins() as $plugin) {
            if ($plugin->hasCpSettings && (! $readOnly || $plugin->hasReadOnlyCpSettings)) {
                $settings[$label][$plugin->handle] = [
                    'url' => Url::cpUrl('settings/plugins/'.$plugin->handle),
                    'icon' => $this->pluginsService->getPluginIconSvg($plugin->handle),
                    'label' => $plugin->name,
                ];
            }
        }

        $settings = array_filter($this->apply($settings, $readOnly), fn (array $section): bool => $section !== []);

        return array_map(fn (array $section): array => array_map($this->withLightIcon(...), $section), $settings);
    }

    /**
     * @param  array<string, mixed>  $setting
     * @return array<string, mixed>
     */
    private function withLightIcon(array $setting): array
    {
        $icon = $setting['iconName'] ?? null;

        if (! is_string($icon) || str_contains($icon, '/')) {
            return $setting;
        }

        $name = Icons::resolveIconName($icon);

        if (is_file(CmsAssets::resourcesPath("icons/light/$name.svg"))) {
            $setting['iconName'] = "light/$name";
        }

        return $setting;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $settings
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function apply(array $settings, bool $readOnly): array
    {
        foreach ($readOnly ? $this->readonlyProviders : $this->providers as $section => $providers) {
            $section = $this->sectionLabel($section);

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
     * The untranslated name of a section {@see all()} returns, which stays the
     * same whatever the language.
     */
    public function sectionName(string $label): string
    {
        return $this->sectionNames[$label] ?? $label;
    }

    /** Translates a section's name, remembering which name the label came from. */
    private function sectionLabel(string $name): string
    {
        $label = t($name);
        $this->sectionNames[$label] = $name;

        return $label;
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

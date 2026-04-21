<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Events\RegisterCpSettings;
use CraftCms\Cms\Cp\Events\RegisterReadonlyCpSettings;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\t;

readonly class Settings
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private Plugins $pluginsService
    ) {}

    public function all(): array
    {
        $readOnly = ! $this->generalConfig->allowAdminChanges;
        $settings = [];

        $label = t('System');

        $settings[$label]['general'] = [
            'url' => route('craft.cp.settings.general.index'),
            'icon' => 'light/sliders',
            'label' => t('General'),
        ];

        $settings[$label]['sites'] = [
            'icon' => sprintf('light/%s', Icons::earth()),
            'label' => t('Sites'),
        ];

        if (! $this->generalConfig->headlessMode) {
            $settings[$label]['routes'] = [
                'icon' => 'light/signs-post',
                'label' => t('Routes'),
            ];
        }

        $settings[$label]['users'] = [
            'icon' => 'light/user-group',
            'label' => t('Users'),
        ];

        if ($this->generalConfig->allowAdminChanges) {
            $settings[$label]['addresses'] = [
                'icon' => 'light/map-location',
                'label' => t('Addresses'),
            ];

            $settings[$label]['email'] = [
                'url' => route('craft.cp.settings.email.index'),
                'icon' => 'light/envelope',
                'label' => t('Email'),
            ];
        }

        $settings[$label]['plugins'] = [
            'icon' => 'light/plug',
            'label' => t('Plugins'),
        ];

        $label = t('Content');

        $settings[$label]['sections'] = [
            'icon' => 'light/newspaper',
            'label' => t('Sections'),
        ];
        $settings[$label]['entry-types'] = [
            'icon' => 'light/files',
            'label' => t('Entry Types'),
        ];
        $settings[$label]['fields'] = [
            'icon' => 'light/pen-to-square',
            'label' => t('Fields'),
        ];

        $label = t('Media');

        $settings[$label]['assets'] = [
            'icon' => 'light/image',
            'label' => t('Assets'),
        ];
        $settings[$label]['filesystems'] = [
            'icon' => 'light/folder-open',
            'label' => t('Filesystems'),
        ];

        $label = t('Plugins');

        foreach ($this->pluginsService->getAllPlugins() as $plugin) {
            if ($plugin->hasCpSettings && (! $readOnly || $plugin->hasReadOnlyCpSettings)) {
                $settings[$label][$plugin->handle] = [
                    'url' => 'settings/plugins/'.$plugin->handle,
                    'icon' => $this->pluginsService->getPluginIconSvg($plugin->handle),
                    'label' => $plugin->name,
                ];
            }
        }

        $event = $readOnly
            ? new RegisterReadonlyCpSettings($settings)
            : new RegisterCpSettings($settings);

        if (Event::hasListeners($event::class)) {
            Event::dispatch($event);

            return $event->settings;
        }

        return $settings;
    }
}

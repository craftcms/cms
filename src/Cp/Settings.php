<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use craft\events\RegisterCpSettingsEvent;
use craft\helpers\Cp as CpHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\t;

class Settings
{
    public function __construct(
        public Plugins $pluginsService
    ) {}

    /**
     * Returns the list of settings.
     */
    public function all(): array
    {
        $readOnly = ! Cms::config()->allowAdminChanges;
        $settings = [];

        $label = t('System');

        $settings[$label]['general'] = [
            'url' => route('craft.cp.settings.general.index'),
            'icon' => 'light/sliders',
            'label' => t('General'),
        ];

        $settings[$label]['sites'] = [
            'icon' => sprintf('light/%s', CpHelper::earthIcon()),
            'label' => t('Sites'),
        ];

        if (! Cms::config()->headlessMode) {
            $settings[$label]['routes'] = [
                'icon' => 'light/signs-post',
                'label' => t('Routes'),
            ];
        }

        $settings[$label]['users'] = [
            'icon' => 'light/user-group',
            // 'iconMask' => '@craftcms/resources/icons/light/user-group.svg',
            'label' => t('Users'),
        ];
        if (Cms::config()->allowAdminChanges) {
            $settings[$label]['addresses'] = [
                'icon' => 'light/map-location',
                'label' => t('Addresses'),
            ];
        }
        $settings[$label]['email'] = [
            'icon' => 'light/envelope',
            'label' => t('Email'),
        ];
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

        // @TODO ask Rias for help with this
        // // Fire a 'registerCpSettings' event
        // $eventName = $readOnly ? self::EVENT_REGISTER_READ_ONLY_CP_SETTINGS : self::EVENT_REGISTER_CP_SETTINGS;
        // if ($this->hasEventHandlers($eventName)) {
        //     $event = new RegisterCpSettingsEvent(['settings' => $settings]);
        //     $this->trigger($eventName, $event);
        //
        //     return $event->settings;
        // }

        return $settings;
    }
}

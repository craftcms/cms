<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig\variables;

use Craft;
use craft\base\Plugin;
use craft\base\UtilityInterface;
use craft\events\RegisterCpNavItemsEvent;
use craft\helpers\Cp as CpHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use yii\base\Component;

/**
 * CP functions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Cp extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterCpNavItemsEvent The event that is triggered when registering Control Panel nav items.
     */
    const EVENT_REGISTER_CP_NAV_ITEMS = 'registerCpNavItems';

    // Public Methods
    // =========================================================================

    /**
     * Returns the Control Panel nav items.
     *
     * @return array
     */
    public function nav(): array
    {
        $navItems = [
            [
                'label' => Craft::t('app', 'Dashboard'),
                'url' => 'dashboard',
                'icon' => 'gauge'
            ],
        ];

        if (Craft::$app->getSections()->getTotalEditableSections()) {
            $navItems[] = [
                'label' => Craft::t('app', 'Entries'),
                'url' => 'entries',
                'icon' => 'section'
            ];
        }

        $globals = Craft::$app->getGlobals()->getEditableSets();

        if (!empty($globals)) {
            $navItems[] = [
                'label' => Craft::t('app', 'Globals'),
                'url' => 'globals/'.$globals[0]->handle,
                'icon' => 'globe'
            ];
        }

        if (Craft::$app->getCategories()->getEditableGroupIds()) {
            $navItems[] = [
                'label' => Craft::t('app', 'Categories'),
                'url' => 'categories',
                'icon' => 'categories'
            ];
        }

        if (Craft::$app->getVolumes()->getTotalViewableVolumes()) {
            $navItems[] = [
                'label' => Craft::t('app', 'Assets'),
                'url' => 'assets',
                'icon' => 'assets'
            ];
        }

        if (Craft::$app->getEdition() === Craft::Pro && Craft::$app->getUser()->checkPermission('editUsers')) {
            $navItems[] = [
                'label' => Craft::t('app', 'Users'),
                'url' => 'users',
                'icon' => 'users'
            ];
        }

        // Add any Plugin nav items
        /** @var Plugin[] $plugins */
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($plugins as $plugin) {
            if ($plugin::hasCpSection()) {
                $pluginHandle = $plugin->getHandle();

                if (Craft::$app->getUser()->checkPermission('accessPlugin-'.$pluginHandle)) {
                    $iconPath = $plugin->getBasePath().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'icon-mask.svg';

                    if (is_file($iconPath)) {
                        $iconSvg = file_get_contents($iconPath);
                    } else {
                        $iconSvg = false;
                    }

                    $navItems[] = [
                        'label' => $plugin->name,
                        'url' => StringHelper::toLowerCase($pluginHandle),
                        'iconSvg' => $iconSvg
                    ];
                }
            }
        }

        if (Craft::$app->getUser()->getIsAdmin()) {
            $navItems[] = [
                'url' => 'settings',
                'label' => Craft::t('app', 'Settings'),
                'icon' => 'settings'
            ];
        }

        $utilities = Craft::$app->getUtilities()->getAuthorizedUtilityTypes();

        if (!empty($utilities)) {
            $badgeCount = 0;

            foreach ($utilities as $class) {
                /** @var UtilityInterface $class */
                $badgeCount += $class::badgeCount();
            }

            $navItems[] = [
                'url' => 'utilities',
                'label' => Craft::t('app', 'Utilities'),
                'icon' => 'tool',
                'badgeCount' => $badgeCount
            ];
        }

        // Allow plugins to modify the nav
        $event = new RegisterCpNavItemsEvent([
            'navItems' => $navItems
        ]);
        $this->trigger(self::EVENT_REGISTER_CP_NAV_ITEMS, $event);
        $navItems = $event->navItems;

        // Figure out which item is selected, and normalize the items
        $path = Craft::$app->getRequest()->getPathInfo();

        if ($path === 'myaccount') {
            $path = 'users';
        }

        $foundSelectedItem = false;

        foreach ($navItems as &$item) {
            if (!$foundSelectedItem && ($item['url'] == $path || StringHelper::startsWith($path, $item['url'].'/'))) {
                $item['sel'] = true;
                $foundSelectedItem = true;
            } else {
                $item['sel'] = false;
            }

            if (!isset($item['id'])) {
                $item['id'] = 'nav-'.preg_replace('/[^\w\-_]/', '', $item['url']);
            }

            $item['url'] = UrlHelper::url($item['url']);

            if (!isset($item['badgeCount'])) {
                $item['badgeCount'] = 0;
            }
        }

        return $navItems;
    }

    /**
     * Returns the list of settings.
     *
     * @return array
     */
    public function settings(): array
    {
        $settings = [];

        $label = Craft::t('app', 'System');

        $settings[$label]['general'] = [
            'icon' => 'general',
            'label' => Craft::t('app', 'General')
        ];
        $settings[$label]['sites'] = [
            'icon' => 'world',
            'label' => Craft::t('app', 'Sites')
        ];
        $settings[$label]['routes'] = [
            'icon' => 'routes',
            'label' => Craft::t('app', 'Routes')
        ];

        if (Craft::$app->getEdition() === Craft::Pro) {
            $settings[$label]['users'] = [
                'icon' => 'users',
                'label' => Craft::t('app', 'Users')
            ];
        }

        $settings[$label]['email'] = [
            'icon' => 'mail',
            'label' => Craft::t('app', 'Email')
        ];
        $settings[$label]['plugins'] = [
            'icon' => 'plugin',
            'label' => Craft::t('app', 'Plugins')
        ];

        $label = Craft::t('app', 'Content');

        $settings[$label]['fields'] = [
            'icon' => 'field',
            'label' => Craft::t('app', 'Fields')
        ];
        $settings[$label]['sections'] = [
            'icon' => 'section',
            'label' => Craft::t('app', 'Sections')
        ];
        $settings[$label]['assets'] = [
            'icon' => 'assets',
            'label' => Craft::t('app', 'Assets')
        ];
        $settings[$label]['globals'] = [
            'icon' => 'globe',
            'label' => Craft::t('app', 'Globals')
        ];
        $settings[$label]['categories'] = [
            'icon' => 'categories',
            'label' => Craft::t('app', 'Categories')
        ];
        $settings[$label]['tags'] = [
            'icon' => 'tags',
            'label' => Craft::t('app', 'Tags')
        ];

        $label = Craft::t('app', 'Plugins');

        $pluginsService = Craft::$app->getPlugins();

        foreach ($pluginsService->getAllPlugins() as $plugin) {
            /** @var Plugin $plugin */
            if ($plugin->hasSettings) {
                $pluginHandle = $plugin->getHandle();

                $settings[$label][$pluginHandle] = [
                    'url' => 'settings/plugins/'.StringHelper::toLowerCase($pluginHandle),
                    'iconSvg' => $pluginsService->getPluginIconSvg($pluginHandle),
                    'label' => $plugin->name
                ];
            }
        }

        return $settings;
    }

    /**
     * Returns whether the CP alerts are cached.
     *
     * @return bool
     */
    public function areAlertsCached(): bool
    {
        // The license key status gets cached on each Elliott request
        return (Craft::$app->getEt()->getLicenseKeyStatus() !== false);
    }

    /**
     * Returns an array of alerts to display in the CP.
     *
     * @return array
     */
    public function getAlerts(): array
    {
        return CpHelper::alerts(Craft::$app->getRequest()->getPathInfo());
    }
}

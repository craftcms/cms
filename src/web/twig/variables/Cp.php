<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\base\Plugin;
use craft\base\UtilityInterface;
use craft\events\RegisterCpNavItemsEvent;
use craft\helpers\Cp as CpHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use GuzzleHttp\Exception\ServerException;
use yii\base\Component;

/**
 * CP functions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
     * Returns the Craft ID account.
     *
     * @return array|null
     */
    public function craftIdAccount()
    {
        try {
            return Craft::$app->getPluginStore()->getCraftIdAccount();
        } catch (ServerException $e) {
            return null;
        }
    }

    /**
     * Returns the Craft ID account URL.
     *
     * @return string
     */
    public function craftIdAccountUrl()
    {
        return Craft::$app->getPluginStore()->craftIdEndpoint . '/account';
    }

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
                'fontIcon' => 'gauge'
            ],
        ];

        if (Craft::$app->getSections()->getTotalEditableSections()) {
            $navItems[] = [
                'label' => Craft::t('app', 'Entries'),
                'url' => 'entries',
                'fontIcon' => 'section'
            ];
        }

        if (!empty(Craft::$app->getGlobals()->getEditableSets())) {
            $navItems[] = [
                'label' => Craft::t('app', 'Globals'),
                'url' => 'globals',
                'fontIcon' => 'globe'
            ];
        }

        if (Craft::$app->getCategories()->getEditableGroupIds()) {
            $navItems[] = [
                'label' => Craft::t('app', 'Categories'),
                'url' => 'categories',
                'fontIcon' => 'categories'
            ];
        }

        if (Craft::$app->getVolumes()->getTotalViewableVolumes()) {
            $navItems[] = [
                'label' => Craft::t('app', 'Assets'),
                'url' => 'assets',
                'fontIcon' => 'assets'
            ];
        }

        if (Craft::$app->getEdition() === Craft::Pro && Craft::$app->getUser()->checkPermission('editUsers')) {
            $navItems[] = [
                'label' => Craft::t('app', 'Users'),
                'url' => 'users',
                'fontIcon' => 'users'
            ];
        }

        // Add any Plugin nav items
        /** @var Plugin[] $plugins */
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($plugins as $plugin) {
            if (
                $plugin->hasCpSection &&
                Craft::$app->getUser()->checkPermission('accessPlugin-' . $plugin->id) &&
                ($pluginNavItem = $plugin->getCpNavItem()) !== null
            ) {
                $navItems[] = $pluginNavItem;
            }
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
                'fontIcon' => 'tool',
                'badgeCount' => $badgeCount
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin()) {
            $navItems[] = [
                'url' => 'settings',
                'label' => Craft::t('app', 'Settings'),
                'fontIcon' => 'settings'
            ];
            $navItems[] = [
                'url' => 'plugin-store',
                'label' => Craft::t('app', 'Plugin Store'),
                'fontIcon' => 'plugin'
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
            if (!$foundSelectedItem && ($item['url'] == $path || StringHelper::startsWith($path, $item['url'] . '/'))) {
                $item['sel'] = true;
                $foundSelectedItem = true;
            } else {
                $item['sel'] = false;
            }

            if (!isset($item['id'])) {
                $item['id'] = 'nav-' . preg_replace('/[^\w\-_]/', '', $item['url']);
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
            'icon' => '@app/icons/sliders.svg',
            'label' => Craft::t('app', 'General')
        ];
        $settings[$label]['sites'] = [
            'icon' => '@app/icons/world.svg',
            'label' => Craft::t('app', 'Sites')
        ];
        $settings[$label]['routes'] = [
            'icon' => '@app/icons/routes.svg',
            'label' => Craft::t('app', 'Routes')
        ];
        $settings[$label]['users'] = [
            'icon' => '@app/icons/users.svg',
            'label' => Craft::t('app', 'Users')
        ];
        $settings[$label]['email'] = [
            'icon' => '@app/icons/envelope.svg',
            'label' => Craft::t('app', 'Email')
        ];
        $settings[$label]['plugins'] = [
            'icon' => '@app/icons/plugin.svg',
            'label' => Craft::t('app', 'Plugins')
        ];

        $label = Craft::t('app', 'Content');

        $settings[$label]['fields'] = [
            'icon' => '@app/icons/field.svg',
            'label' => Craft::t('app', 'Fields')
        ];
        $settings[$label]['sections'] = [
            'icon' => '@app/icons/newspaper.svg',
            'label' => Craft::t('app', 'Sections')
        ];
        $settings[$label]['assets'] = [
            'icon' => '@app/icons/photo.svg',
            'label' => Craft::t('app', 'Assets')
        ];
        $settings[$label]['globals'] = [
            'icon' => '@app/icons/globe.svg',
            'label' => Craft::t('app', 'Globals')
        ];
        $settings[$label]['categories'] = [
            'icon' => '@app/icons/folder-open.svg',
            'label' => Craft::t('app', 'Categories')
        ];
        $settings[$label]['tags'] = [
            'icon' => '@app/icons/tags.svg',
            'label' => Craft::t('app', 'Tags')
        ];

        $label = Craft::t('app', 'Plugins');

        $pluginsService = Craft::$app->getPlugins();

        foreach ($pluginsService->getAllPlugins() as $plugin) {
            /** @var Plugin $plugin */
            if ($plugin->hasCpSettings) {
                $settings[$label][$plugin->id] = [
                    'url' => 'settings/plugins/' . $plugin->id,
                    'icon' => $pluginsService->getPluginIconSvg($plugin->id),
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
        // The license key status gets cached on each Craftnet request
        return (Craft::$app->getCache()->get('licenseKeyStatus') !== false);
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

<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\base\Plugin;
use craft\app\helpers\Cp as CpHelper;
use craft\app\helpers\Io as IoHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\Url;

/**
 * CP functions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Cp
{
    // Public Methods
    // =========================================================================

    /**
     * Get the sections of the CP.
     *
     * @param integer $iconSize The icon size
     *
     * @return array
     */
    public function nav($iconSize = 32)
    {
        $nav['dashboard'] = [
            'label' => Craft::t('app', 'Dashboard'),
            'icon' => 'gauge'
        ];

        if (Craft::$app->getSections()->getTotalEditableSections()) {
            $nav['entries'] = [
                'label' => Craft::t('app', 'Entries'),
                'icon' => 'section'
            ];
        }

        $globals = Craft::$app->getGlobals()->getEditableSets();

        if ($globals) {
            $nav['globals'] = [
                'label' => Craft::t('app', 'Globals'),
                'url' => 'globals/'.$globals[0]->handle,
                'icon' => 'globe'
            ];
        }

        if (Craft::$app->getCategories()->getEditableGroupIds()) {
            $nav['categories'] = [
                'label' => Craft::t('app', 'Categories'),
                'icon' => 'categories'
            ];
        }

        if (Craft::$app->getVolumes()->getTotalViewableVolumes()) {
            $nav['assets'] = [
                'label' => Craft::t('app', 'Assets'),
                'icon' => 'assets'
            ];
        }

        if (Craft::$app->getEdition() == Craft::Pro && Craft::$app->getUser()->checkPermission('editUsers')) {
            $nav['users'] = [
                'label' => Craft::t('app', 'Users'),
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
                    $lcHandle = StringHelper::toLowerCase($pluginHandle);
                    $iconPath = Craft::$app->getPath()->getPluginsPath().'/'.$lcHandle.'/resources/icon-mask.svg';

                    if (IoHelper::fileExists($iconPath)) {
                        $iconSvg = IoHelper::getFileContents($iconPath);
                    } else {
                        $iconSvg = false;
                    }

                    $nav[$lcHandle] = [
                        'label' => $plugin->name,
                        'iconSvg' => $iconSvg
                    ];
                }
            }
        }

        if (Craft::$app->getUser()->getIsAdmin()) {
            $nav['settings'] = [
                'label' => Craft::t('app', 'Settings'),
                'icon' => 'settings'
            ];
        }

        // Allow plugins to modify the nav
        Craft::$app->getPlugins()->call('modifyCpNav', [&$nav]);

        // Figure out which item is selected, and normalize the items
        $firstSegment = Craft::$app->getRequest()->getSegment(1);

        if ($firstSegment == 'myaccount') {
            $firstSegment = 'users';
        }

        foreach ($nav as $handle => &$item) {
            if (is_string($item)) {
                $item = ['label' => $item];
            }

            $item['sel'] = ($handle == $firstSegment);

            if (isset($item['url'])) {
                $item['url'] = Url::getUrl($item['url']);
            } else {
                $item['url'] = Url::getUrl($handle);
            }
        }

        return $nav;
    }

    /**
     * Returns the list of settings.
     *
     * @return array
     */
    public function settings()
    {
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

        if (Craft::$app->getEdition() == Craft::Pro) {
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
     * @return boolean
     */
    public function areAlertsCached()
    {
        // The license key status gets cached on each Elliott request
        return (Craft::$app->getEt()->getLicenseKeyStatus() !== false);
    }

    /**
     * Returns an array of alerts to display in the CP.
     *
     * @return array
     */
    public function getAlerts()
    {
        return CpHelper::getAlerts(Craft::$app->getRequest()->getPathInfo());
    }
}

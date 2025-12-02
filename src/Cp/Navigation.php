<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use Craft;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;

class Navigation
{
    public function __construct(
        private readonly Plugins $plugins,
        private readonly Utilities $utilities,
        private readonly GeneralConfig $generalConfig
    ) {}

    public function getItems(): array
    {
        $isAdmin = Auth::user()?->isAdmin();
        $generalConfig = Cms::config();

        $navItems = [
            [
                'label' => t('Dashboard'),
                'url' => 'dashboard',
                'icon' => 'gauge',
            ],
        ];

        if (Sections::getTotalEditableSections()) {
            $navItems[] = [
                'label' => t('Entries'),
                'url' => 'entries',
                'icon' => 'newspaper',
            ];
        }

        /**
         * @TODO currently throwing a SQL error
         *
         * Base table or view not found: 1146 Table 'db.globalsets' doesn't exist
         */
        // if (! empty(Craft::$app->getGlobals()->getEditableSets())) {
        //     $navItems[] = [
        //         'label' => t('Globals'),
        //         'url' => 'globals',
        //         'icon' => 'globe',
        //     ];
        // }

        /**
         * @TODO throwing a SQL error
         * Base table or view not found: 1146 Table 'db.categorygroups' doesn't exist
         */
        // if (Craft::$app->getCategories()->getEditableGroupIds()) {
        //     $navItems[] = [
        //         'label' => t('Categories'),
        //         'url' => 'categories',
        //         'icon' => 'sitemap',
        //     ];
        // }

        if (Craft::$app->getVolumes()->getTotalViewableVolumes()) {
            $navItems[] = [
                'label' => t('Assets'),
                'url' => 'assets',
                'icon' => 'image',
            ];
        }

        if (
            Edition::get() !== Edition::Solo &&
            Auth::user()->can('viewUsers')
        ) {
            $navItems[] = [
                'label' => t('Users'),
                'url' => 'users',
                'icon' => 'user-group',
            ];
        }

        // Add any Plugin nav items
        $plugins = $this->plugins->getAllPlugins();

        foreach ($plugins as $plugin) {
            if (
                $plugin->hasCpSection &&
                Craft::$app->getUser()->checkPermission('accessPlugin-'.$plugin->handle) &&
                ($pluginNavItem = $plugin->getCpNavItem()) !== null
            ) {
                $navItems[] = $pluginNavItem;
            }
        }

        if ($isAdmin) {
            if ($generalConfig->enableGql) {
                $subNavItems = [];

                if ($generalConfig->allowAdminChanges) {
                    $subNavItems['schemas'] = [
                        'label' => t('Schemas'),
                        'url' => 'graphql/schemas',
                    ];
                }

                $subNavItems['tokens'] = [
                    'label' => t('Tokens'),
                    'url' => 'graphql/tokens',
                ];

                $subNavItems['graphiql'] = [
                    'label' => 'GraphiQL',
                    'url' => 'graphiql',
                    'external' => true,
                ];

                $navItems[] = [
                    'label' => 'GraphQL',
                    'url' => 'graphql',
                    'icon' => 'graphql',
                    'subnav' => $subNavItems,
                ];
            }
        }

        $utilities = $this->utilities->getAuthorizedUtilityTypes();
        $badgeCount = 0;
        foreach ($utilities as $class) {
            /** @var Utility $class */
            $badgeCount += $class::badgeCount();
        }
        $navItems[] = [
            'url' => 'utilities',
            'label' => t('Utilities'),
            'icon' => 'wrench',
            'badgeCount' => $badgeCount,
        ];

        if ($isAdmin) {
            $navItems[] = [
                'url' => 'settings',
                'label' => t('Settings'),
                'icon' => $this->generalConfig->allowAdminChanges ? 'gear' : 'gear-slash',
            ];

            $navItems[] = [
                'url' => 'plugin-store',
                'label' => t('Plugin Store'),
                'icon' => 'plug',
            ];
        }

        // Fire a 'registerCpNavItems' event
        // @TODO Bring this back
        // if ($this->hasEventHandlers(self::EVENT_REGISTER_CP_NAV_ITEMS)) {
        //     $event = new RegisterCpNavItemsEvent(['navItems' => $navItems]);
        //     $this->trigger(self::EVENT_REGISTER_CP_NAV_ITEMS, $event);
        //     $navItems = $event->navItems;
        // }

        // Figure out which item is selected, and normalize the items
        $path = Craft::$app->getRequest()->getPathInfo();

        if ($path === 'myaccount' || str_starts_with((string) $path, 'myaccount/')) {
            $path = 'users';
        }

        $foundSelectedItem = false;

        foreach ($navItems as &$item) {
            if (! $foundSelectedItem && ($item['url'] == $path || str_starts_with((string) $path, $item['url'].'/'))) {
                $item['sel'] = true;
                if (! isset($item['subnav'])) {
                    $item['subnav'] = false;
                }
                $foundSelectedItem = true;

                // Modify aria-current value for exact page vs. subpages
                $item['linkAttributes']['aria']['current'] = $item['url'] === $path ? 'page' : 'true';
            } else {
                $item['sel'] = false;
                if (! isset($item['subnav'])) {
                    $item['subnav'] = false;
                }
            }

            if (! isset($item['id'])) {
                $item['id'] = 'nav-'.preg_replace('/[^\w\-_]/', '', (string) $item['url']);
            }

            $item['url'] = UrlHelper::url($item['url']);

            if (! isset($item['external'])) {
                $item['external'] = false;
            }

            if (! isset($item['badgeCount'])) {
                $item['badgeCount'] = 0;
            }
        }

        return $navItems;
    }
}

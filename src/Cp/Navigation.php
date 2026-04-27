<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use Craft;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Events\RegisterCpNavItems;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;

readonly class Navigation
{
    public function __construct(
        private Request $request,
        private Plugins $plugins,
        private Utilities $utilities,
        private GeneralConfig $generalConfig
    ) {}

    public function getItems(): array
    {
        $user = Auth::user();
        $isAdmin = $user?->isAdmin();

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

        if (Volumes::getTotalViewableVolumes()) {
            $navItems[] = [
                'label' => t('Assets'),
                'url' => 'assets',
                'icon' => 'image',
            ];
        }

        if (
            Edition::get() !== Edition::Solo &&
            $user?->can('viewUsers')
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
                $user->can('accessPlugin-'.$plugin->handle) &&
                ($pluginNavItem = $plugin->getCpNavItem()) !== null
            ) {
                $navItems[] = $pluginNavItem;
            }
        }

        if ($isAdmin) {
            if ($this->generalConfig->enableGql) {
                $subNavItems = [];

                if ($this->generalConfig->allowAdminChanges) {
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
                    'icon' => 'custom-icons/graphql',
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

        event($event = new RegisterCpNavItems($navItems));

        $navItems = $event->navItems;

        // Figure out which item is selected, and normalize the items
        $path = $this->request->getPathInfo();

        if ($path === 'myaccount' || str_starts_with($path, 'myaccount/')) {
            $path = 'users';
        }

        $foundSelectedItem = false;

        foreach ($navItems as &$item) {
            if (! $foundSelectedItem && ($item['url'] == $path || str_starts_with($path, $item['url'].'/'))) {
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

            $item['url'] = Url::url($item['url']);

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

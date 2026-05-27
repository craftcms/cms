<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Events\CpNavItemsResolving;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

readonly class Navigation
{
    public function __construct(
        private Request $request,
        private Plugins $plugins,
        private Utilities $utilities,
        private GeneralConfig $generalConfig,
        private ElementSources $elementSources,
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
            $entryPages = $this->elementSources->getPages(Entry::class);

            if ($entryPages->isNotEmpty()) {
                $entryPageSettings = $this->elementSources->getPageSettings(Entry::class);

                foreach ($entryPages as $page) {
                    $navItems[] = [
                        'label' => $page !== 'Entries' ? t($page, category: 'site') : t('Entries'),
                        'url' => sprintf('content/%s', Str::slug($page)),
                        'icon' => $entryPageSettings[$page]['icon'] ?? 'newspaper',
                    ];
                }
            } else {
                $navItems[] = [
                    'label' => t('Entries'),
                    'url' => 'content/entries',
                    'icon' => 'newspaper',
                ];
            }
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
            Gate::check('viewUsers')
        ) {
            $navItems[] = [
                'label' => t('Users'),
                'url' => 'users',
                'icon' => 'user-group',
            ];
        }

        if ($user?->can('viewImportConfigs') || $user?->can('viewImportRuns')) {
            $subNavItems = [];

            if ($user?->can('viewImportConfigs')) {
                $subNavItems['configs'] = [
                    'label' => t('Configs'),
                    'url' => 'import/configs',
                ];
            }

            if ($user?->can('viewImportRuns')) {
                $subNavItems['runs'] = [
                    'label' => t('Runs'),
                    'url' => 'import/runs',
                ];
            }

            $navItems[] = [
                'label' => t('Import'),
                'url' => 'import',
                'icon' => 'arrow-up-to-bracket',
                'subnav' => $subNavItems,
            ];
        }

        // Add any Plugin nav items
        $plugins = $this->plugins->getAllPlugins();

        foreach ($plugins as $plugin) {
            if (
                $plugin->hasCpSection &&
                Gate::check('accessPlugin-'.$plugin->handle) &&
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
                        'url' => cp_url('graphql/schemas'),
                    ];
                }

                $subNavItems['tokens'] = [
                    'label' => t('Tokens'),
                    'url' => cp_url('graphql/tokens'),
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

        if ($utilities->isNotEmpty()) {
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
        }

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

        event($event = new CpNavItemsResolving($navItems));

        $navItems = $event->navItems;

        // Figure out which item is selected, and normalize the items
        $path = $this->currentCpPath();

        if ($path === 'myaccount' || str_starts_with($path, 'myaccount/')) {
            $path = 'users';
        }

        $foundSelectedItem = false;

        foreach ($navItems as &$item) {
            $itemPath = $this->navItemPath($item['url']);
            $subnavSelected = false;

            if (! isset($item['subnav'])) {
                $item['subnav'] = false;
            } elseif (is_array($item['subnav'])) {
                foreach ($item['subnav'] as &$subnavItem) {
                    $subnavItemPath = $this->navItemPath($subnavItem['url']);
                    $subnavItemSelected = $this->pathMatches($path, $subnavItemPath);

                    if ($subnavItemSelected) {
                        $subnavItem['sel'] = true;
                        $subnavSelected = true;
                        $subnavItem['linkAttributes']['aria']['current'] = $subnavItemPath === $path ? 'page' : 'true';
                    }

                    $subnavItem['url'] = Url::url($subnavItem['url']);
                    $subnavItem['external'] ??= false;
                    $subnavItem['badgeCount'] ??= 0;
                }
                unset($subnavItem);
            }

            if (! $foundSelectedItem && ($this->pathMatches($path, $itemPath) || $subnavSelected)) {
                $item['sel'] = true;
                $foundSelectedItem = true;

                // Modify aria-current value for exact page vs. subpages
                $item['linkAttributes']['aria']['current'] = $itemPath === $path ? 'page' : 'true';
            } else {
                $item['sel'] = false;
            }

            if (! isset($item['id'])) {
                $item['id'] = 'nav-'.preg_replace('/[^\w\-_]/', '', Str::ascii(str_replace('/', '-', $item['url'])));
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

    private function currentCpPath(): string
    {
        return $this->request->craftPath();
    }

    private function navItemPath(string $url): string
    {
        return $this->withoutCpTrigger((string) parse_url($url, PHP_URL_PATH));
    }

    private function withoutCpTrigger(string $path): string
    {
        $path = trim(rawurldecode($path), '/');
        $cpTrigger = trim((string) $this->generalConfig->cpTrigger, '/');

        if ($cpTrigger === '') {
            return $path;
        }

        if ($path === $cpTrigger) {
            return '';
        }

        if (str_starts_with($path, $cpTrigger.'/')) {
            return substr($path, strlen($cpTrigger) + 1);
        }

        return $path;
    }

    private function pathMatches(string $path, string $itemPath): bool
    {
        return $itemPath !== '' && ($path === $itemPath || str_starts_with($path, $itemPath.'/'));
    }
}

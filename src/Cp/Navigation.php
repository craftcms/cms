<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\NavItem;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
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

    /** @return NavItem[] */
    public function getItems(): array
    {
        $user = currentUser();
        $isAdmin = $user?->isAdmin();

        $navItems = collect([
            new NavItem()
                ->label(t('Dashboard'))
                ->url('dashboard')
                ->icon('gauge'),
        ]);

        if (Sections::getTotalEditableSections()) {
            $entryPages = $this->elementSources->getPages(Entry::class);

            if ($entryPages->isNotEmpty()) {
                $entryPageSettings = $this->elementSources->getPageSettings(Entry::class);

                $navItems = $navItems->merge(
                    $entryPages->map(fn (string $page) => new NavItem()
                        ->when(
                            $page === 'Entries',
                            fn (NavItem $item) => $item->label(t('Entries')),
                            fn (NavItem $item) => $item->label(t($page, category: 'site')),
                        )
                        ->url(sprintf('content/%s', Str::slug($page)))
                        ->icon($entryPageSettings[$page]['icon'] ?? 'newspaper')
                    )
                );
            } else {
                $navItems->add(new NavItem()
                    ->label(t('Entries'))
                    ->url('content/entries')
                    ->icon('newspaper'));
            }
        }

        if (Volumes::getTotalViewableVolumes()) {
            $navItems->add(new NavItem()
                ->label(t('Assets'))
                ->url('assets')
                ->icon('image'));
        }

        if (
            Edition::get() !== Edition::Solo &&
            Gate::check('viewUsers')
        ) {
            $navItems->add(new NavItem()
                ->label(t('Users'))
                ->url('users')
                ->icon('user-group'));
        }

        // Add any Plugin nav items
        foreach ($this->plugins->getAllPlugins() as $plugin) {
            if (! $plugin->hasCpSection) {
                continue;
            }

            if (! Gate::check('accessPlugin-'.$plugin->handle)) {
                continue;
            }

            if (($pluginNavItem = $plugin->getCpNavItem()) === null) {
                continue;
            }

            if (! $pluginNavItem instanceof NavItem) {
                $pluginNavItem = new NavItem($pluginNavItem);
            }

            $navItems->add($pluginNavItem);
        }

        if ($isAdmin && $this->generalConfig->enableGql) {
            $subNavItems = collect()
                ->when(
                    $this->generalConfig->allowAdminChanges,
                    fn (Collection $subNavItems) => $subNavItems->add(
                        new NavItem()
                            ->label(t('Schemas'))
                            ->url(cp_url('graphql/schemas')),
                    )
                )
                ->add(new NavItem()->label(t('Tokens'))->url(cp_url('graphql/tokens')))
                ->add(new NavItem()->label('GraphiQL')->url('graphiql')->external(true));

            $navItems->add(new NavItem()
                ->label('GraphQL')
                ->url('graphql')
                ->icon('custom-icons/graphql')
                ->subnav($subNavItems->all()));
        }

        $utilities = $this->utilities->getAuthorizedUtilityTypes();

        if ($utilities->isNotEmpty()) {
            $badgeCount = $utilities->sum(function (string $class) {
                /** @var class-string<Utility> $class */
                return $class::badgeCount();
            });

            $navItems->add(new NavItem()
                ->label(t('Utilities'))
                ->url('utilities')
                ->icon('wrench')
                ->badgeCount($badgeCount));
        }

        if ($isAdmin) {
            $navItems->add(new NavItem()
                ->label(t('Settings'))
                ->url('settings')
                ->icon($this->generalConfig->allowAdminChanges ? 'gear' : 'gear-slash'));

            $navItems->add(new NavItem()
                ->label(t('Plugin Store'))
                ->url('plugin-store')
                ->icon('plug'));
        }

        event($event = new CpNavItemsResolving($navItems->all()));

        // Figure out which item is selected, and normalize the items
        $path = $this->request->craftPath();

        if ($path === 'myaccount' || str_starts_with($path, 'myaccount/')) {
            $path = 'users';
        }

        $foundSelectedItem = false;

        return collect($event->navItems)->map(function (NavItem $item) use ($path, &$foundSelectedItem) {
            $itemPath = $this->navItemPath($item->url);
            $subnavSelected = false;

            if (is_array($item->subnav)) {
                $item->subnav = collect($item->subnav)->map(function (NavItem $subnavItem) use ($path, &$subnavSelected) {
                    $subnavItemPath = $this->navItemPath($subnavItem->url);
                    $subnavItemSelected = $this->pathMatches($path, $subnavItemPath);

                    if ($subnavItemSelected) {
                        $subnavItem->selected = true;
                        $subnavSelected = true;
                        $subnavItem->linkAttributes['aria']['current'] = $subnavItemPath === $path ? 'page' : 'true';
                    }

                    $subnavItem->url = Url::url($subnavItem->url);

                    return $subnavItem;
                })->all();
            }

            if (! $foundSelectedItem && ($this->pathMatches($path, $itemPath) || $subnavSelected)) {
                $item->selected = true;
                $foundSelectedItem = true;

                // Modify aria-current value for exact page vs. subpages
                $item->linkAttributes['aria']['current'] = $itemPath === $path ? 'page' : 'true';
            }

            $item->id ??= 'nav-'.preg_replace('/[^\w\-_]/', '', Str::ascii(str_replace('/', '-', $item['url'])));
            $item->url = Url::url($item->url);

            return $item;
        })->all();
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

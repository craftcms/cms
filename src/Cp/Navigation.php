<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Events\CpNavItemsResolving;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigService;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utility;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

readonly class Navigation
{
    /**
     * Cache tag for every stored nav tree.
     *
     * Most of what the tree is built from is project config, and that's keyed
     * into the cache entry rather than watched — but anything else that
     * reshapes the nav (a permission grant, a plugin toggling its CP section
     * at runtime) invalidates this.
     */
    public const string CACHE_TAG = 'cp-nav';

    /** Bump when the shape of a cached tree changes, to orphan stale entries. */
    private const int CACHE_VERSION = 1;

    /**
     * Backstop for what the key can't see: a user's directly-granted
     * permissions don't live in project config, so a nav built before a grant
     * would otherwise stand until something else moved.
     */
    private const int CACHE_TTL = 3600;

    public function __construct(
        private Request $request,
        private Plugins $plugins,
        private Utilities $utilities,
        private GeneralConfig $generalConfig,
        private ElementSources $elementSources,
        private Settings $settings,
    ) {}

    /**
     * The nav for the current request: the cached tree, with badge counts and
     * the selected trail applied.
     *
     * @return NavItem[]
     */
    public function getItems(): array
    {
        return $this->applySelection(
            $this->applyBadgeCounts($this->getTree())
        );
    }

    /**
     * The nav flattened to the two levels the legacy CP can draw.
     *
     * `_layouts/components/global-sidebar.twig` renders a subnav and stops,
     * and has no notion of a group — a heading with no URL would come out as
     * an unclickable nav item. So a group hands its children up in its place,
     * which is what that template saw before the tree went deeper.
     *
     * @return NavItem[]
     */
    public function getShallowItems(): array
    {
        return array_map(function (NavItem $item): NavItem {
            if (is_array($item->subnav)) {
                $item->subnav = $this->flattenGroups($item->subnav);
            }

            return $item;
        }, $this->getItems());
    }

    /**
     * @param  NavItem[]  $items
     * @return NavItem[]
     */
    private function flattenGroups(array $items): array
    {
        $flattened = [];

        foreach ($items as $item) {
            if ($item->group && is_array($item->subnav)) {
                foreach ($this->flattenGroups($item->subnav) as $child) {
                    $flattened[] = $child;
                }

                continue;
            }

            $flattened[] = $item;
        }

        return $flattened;
    }

    /**
     * The whole navigation tree, structure only.
     *
     * No selection and no badge counts: both are per-request, and leaving them
     * out is what makes this cacheable across every page a user visits, and
     * what lets the front end be handed the tree once rather than on every
     * response.
     *
     * @return NavItem[]
     */
    public function getTree(): array
    {
        $tree = DependencyCache::remember(
            $this->cacheKey(),
            self::CACHE_TTL,
            fn (): array => $this->hydrateArray($this->buildTree()),
            new TagDependency(self::CACHE_TAG),
        );

        return $this->hydrateItems($tree);
    }

    /**
     * Throws away every stored nav tree.
     *
     * Call this when something the cache key can't see has changed — a
     * permission grant, most obviously.
     */
    public static function flushCache(): void
    {
        TagDependency::invalidate(self::CACHE_TAG);
    }

    /**
     * The volatile half: counts that change without the nav's shape changing.
     *
     * Deliberately outside the cache. `Updates::badgeCount()` and friends each
     * hit the database, so they're the part of the nav that can't be stale,
     * and the part that must not keep the rest of it from being cached.
     *
     * @return array<string, int>
     */
    public function getBadgeCounts(): array
    {
        $counts = [];
        $utilities = $this->utilities->getUtilitiesBadgeCount();

        if ($utilities > 0) {
            $counts[$this->itemId('utilities')] = $utilities;
        }

        return $counts;
    }

    /** @return NavItem[] */
    private function buildTree(): array
    {
        $user = currentUser();
        $isAdmin = $user?->isAdmin();

        $navItems = collect([
            new NavItem()
                ->label(t('Dashboard'))
                ->href('dashboard')
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
                        ->href(sprintf('content/%s', Str::slug($page)))
                        ->icon($entryPageSettings[$page]['icon'] ?? 'newspaper')
                        ->subnav($this->sourceSubnav(
                            Entry::class,
                            sprintf('content/%s', Str::slug($page)),
                            $page,
                        ))
                    )
                );
            } else {
                $navItems->add(new NavItem()
                    ->label(t('Entries'))
                    ->href('content/entries')
                    ->icon('newspaper')
                    ->subnav($this->sourceSubnav(Entry::class, 'content/entries')));
            }
        }

        if (Volumes::getTotalViewableVolumes()) {
            $navItems->add(new NavItem()
                ->label(t('Assets'))
                ->href('assets')
                ->icon('image')
                ->subnav($this->sourceSubnav(Asset::class, 'assets')));
        }

        if (
            Edition::get() !== Edition::Solo &&
            Gate::check('viewUsers')
        ) {
            $navItems->add(new NavItem()
                ->label(t('Users'))
                ->href('users')
                ->icon('user-group')
                ->subnav($this->sourceSubnav(User::class, 'users')));
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
                            ->href(cp_url('graphql/schemas')),
                    )
                )
                ->add(new NavItem()->label(t('Tokens'))->href(cp_url('graphql/tokens')))
                ->add(new NavItem()->label('GraphiQL')->href(cp_url('graphql/explore')));

            $navItems->add(new NavItem()
                ->label('GraphQL')
                ->href('graphql')
                ->icon('custom-icons/graphql')
                ->subnav($subNavItems->all()));
        }

        $utilities = $this->utilities->getAuthorizedUtilityTypes();

        if ($utilities->isNotEmpty()) {
            $navItems->add(new NavItem()
                ->label(t('Utilities'))
                ->href('utilities')
                ->icon('wrench')
                ->subnav($this->utilitiesSubnav($utilities)));
        }

        if ($isAdmin) {
            $navItems->add(new NavItem()
                ->label(t('Settings'))
                ->href('settings')
                ->icon($this->generalConfig->allowAdminChanges ? 'gear' : 'gear-slash')
                ->subnav($this->settingsSubnav()));

            $navItems->add(new NavItem()
                ->label(t('Plugin Store'))
                ->href('plugin-store')
                ->icon('plug'));
        }

        event($event = new CpNavItemsResolving($navItems->all()));

        return collect($event->navItems)
            ->map(fn (NavItem $item): NavItem => $this->normalize($item))
            ->all();
    }

    /**
     * An element type's index sources, as nav children.
     *
     * The same list the index's own sidebar draws, so the two can't disagree
     * about what exists or where it lives. A heading row becomes a `group`:
     * a label over its members rather than somewhere to go, which is how the
     * sources sidebar renders one too.
     *
     * A source without a URL of its own — Temporary Uploads, a custom source —
     * falls back to the `?source=` query the index's own sidebar links it by,
     * which is what {@see ContentIndexViewModel::sourceUrl()} does. Dropping
     * them would leave the nav saying less than the sidebar it mirrors.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  string  $indexUri  The index these sources hang off
     * @return NavItem[]
     */
    private function sourceSubnav(string $elementType, string $indexUri, ?string $page = null): array
    {
        $items = [];
        $group = null;

        foreach ($this->elementSources->getSources($elementType, page: $page) as $source) {
            if (($source['type'] ?? null) === ElementSources::TYPE_HEADING) {
                $heading = (string) ($source['heading'] ?? '');

                // A blank heading separates a trailing run of un-configured
                // sources rather than naming one, so it closes the open group
                // instead of starting an empty one.
                $group = $heading === '' ? null : new NavItem()->label($heading)->group(true)->subnav([]);

                if ($group !== null) {
                    $items[] = $group;
                }

                continue;
            }

            $key = (string) ($source['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $item = new NavItem()
                ->label((string) ($source['label'] ?? ''))
                ->href($elementType::sourceCpUri($source, $page) ?? $this->sourceQueryUri($indexUri, $key));

            if ($group !== null) {
                $group->subnav = [...$group->subnav, $item];

                continue;
            }

            $items[] = $item;
        }

        // A heading whose members all turned out to be unreachable would
        // otherwise be left standing over nothing.
        return array_values(array_filter(
            $items,
            fn (NavItem $item): bool => ! $item->group || $item->subnav !== [],
        ));
    }

    /**
     * The index URL with the source named in the query.
     *
     * The “all elements” source is what the bare index already shows, so it's
     * addressed by the index's own URL rather than a query repeating it.
     */
    private function sourceQueryUri(string $indexUri, string $key): string
    {
        return $key === '*'
            ? $indexUri
            : $indexUri.'?source='.rawurlencode($key);
    }

    /**
     * The utilities, as children of the Utilities item.
     *
     * Badge counts are left off — they're request-scoped, and `getBadgeCounts()`
     * fills them in.
     *
     * @param  Collection<int, class-string<Utility>>  $utilities
     * @return NavItem[]
     */
    private function utilitiesSubnav(Collection $utilities): array
    {
        return $utilities
            ->map(fn (string $class) => new NavItem()
                ->label($class::displayName())
                ->href('utilities/'.$class::id())
                ->icon($class::icon()))
            ->values()
            ->all();
    }

    /**
     * The settings screens, grouped the way the settings index groups them.
     *
     * `Cp\Settings` is the one list of these, so the nav reads it rather than
     * keeping a second copy that would drift. Its sections become `group`
     * items: headings over their children rather than places to go.
     *
     * @return NavItem[]
     */
    private function settingsSubnav(): array
    {
        $groups = [];

        foreach ($this->settings->all() as $section => $settings) {
            $children = [];

            foreach ($settings as $handle => $setting) {
                $children[] = $this->settingNavItem($handle, $setting);
            }

            if ($children === []) {
                continue;
            }

            $groups[] = new NavItem()
                ->label($section)
                ->group(true)
                ->subnav($children);
        }

        return $groups;
    }

    /**
     * One settings screen. A plugin ships an `icon.svg` rather than naming an
     * icon, so both channels come across.
     *
     * @param  array<string, mixed>  $setting
     */
    private function settingNavItem(string $handle, array $setting): NavItem
    {
        return new NavItem()
            ->label($setting['label'])
            ->href($setting['url'] ?? 'settings/'.$handle)
            ->icon($setting['iconName'] ?? null)
            ->iconSvg($setting['icon'] ?? null);
    }

    /**
     * Gives an item its id and an absolute URL, and does the same for anything
     * beneath it.
     */
    private function normalize(NavItem $item): NavItem
    {
        $item->id ??= $this->itemId((string) $item->href);

        if ($item->href !== null && $item->href !== '') {
            $item->href = Url::url($item->href);
        }

        if (is_array($item->subnav)) {
            $item->subnav = array_map(
                $this->normalize(...),
                $item->subnav,
            );
        }

        return $item;
    }

    /** The stable id an item is known by, in the tree and in the badge map. */
    private function itemId(string $href): string
    {
        return 'nav-'.preg_replace('/[^\w\-_]/', '', Str::ascii(str_replace('/', '-', $href)));
    }

    /**
     * Marks the trail to the current page.
     *
     * The first match wins, so a deeper item claims its ancestors rather than
     * a shallower one swallowing the branch.
     *
     * @param  NavItem[]  $items
     * @return NavItem[]
     */
    private function applySelection(array $items): array
    {
        $path = $this->request->craftPath();

        if ($path === 'myaccount' || str_starts_with($path, 'myaccount/')) {
            $path = 'users';
        }

        $found = false;

        return $this->selectWithin($items, $path, $found);
    }

    /**
     * @param  NavItem[]  $items
     * @return NavItem[]
     */
    private function selectWithin(array $items, string $path, bool &$found): array
    {
        foreach ($items as $item) {
            if (is_array($item->subnav)) {
                $item->subnav = $this->selectWithin($item->subnav, $path, $found);
            }

            $descendantSelected = is_array($item->subnav) && array_any(
                $item->subnav,
                fn (NavItem $child): bool => $child->selected,
            );

            $itemPath = $this->navItemPath((string) $item->href);

            // A selected descendant claims its ancestors unconditionally —
            // it has already set `$found`, and the trail has to reach the root.
            // Failing that, the first path match wins and closes the question
            // for everything after it.
            if ($descendantSelected || (! $found && $this->pathMatches($path, $itemPath))) {
                $item->selected = true;
                $item->linkAttributes['aria']['current'] = $itemPath === $path ? 'page' : 'true';
                $found = true;
            }
        }

        return $items;
    }

    /**
     * @param  NavItem[]  $items
     * @return NavItem[]
     */
    private function applyBadgeCounts(array $items): array
    {
        $counts = $this->getBadgeCounts();

        if ($counts === []) {
            return $items;
        }

        $apply = function (array $items) use (&$apply, $counts): array {
            foreach ($items as $item) {
                if (isset($counts[$item->id])) {
                    $item->badgeCount = $counts[$item->id];
                }

                if (is_array($item->subnav)) {
                    $item->subnav = $apply($item->subnav);
                }
            }

            return $items;
        };

        return $apply($items);
    }

    /**
     * The cache key for the current request's tree.
     *
     * Everything the tree's *shape* depends on is in here, so a change to any
     * of it lands on a different key rather than needing to be noticed:
     * project config carries sections, volumes, sites, plugins and user
     * groups, and the rest is per-user or per-request config.
     */
    private function cacheKey(): string
    {
        return implode(':', [
            'cp-nav',
            self::CACHE_VERSION,
            currentUser()?->getCraftUserId() ?? 'guest',
            Sites::getCurrentSite()->id ?? 0,
            Edition::get()->value,
            app()->getLocale(),
            (int) $this->generalConfig->allowAdminChanges,
            (int) $this->generalConfig->headlessMode,
            (string) ProjectConfig::get(ProjectConfigService::PATH_DATE_MODIFIED),
        ]);
    }

    /**
     * Objects go into the cache as plain arrays.
     *
     * A `NavItem` is a `Component`, and plugins put their own in the tree —
     * storing those would make the cache hostage to whatever a third party
     * hangs off one.
     *
     * @param  NavItem[]  $items
     * @return array<int, array<string, mixed>>
     */
    private function hydrateArray(array $items): array
    {
        return array_map(function (NavItem $item): array {
            $data = $item->toArray();

            if (is_array($item->subnav)) {
                $data['subnav'] = $this->hydrateArray($item->subnav);
            }

            return $data;
        }, $items);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return NavItem[]
     */
    private function hydrateItems(array $items): array
    {
        return array_map(function (array $data): NavItem {
            $subnav = $data['subnav'] ?? false;
            unset($data['subnav']);

            $item = new NavItem($data);

            if (is_array($subnav)) {
                $item->subnav = $this->hydrateItems($subnav);
            }

            return $item;
        }, $items);
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

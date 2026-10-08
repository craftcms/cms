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
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class Navigation
{
    public function __construct(
        private Request $request,
        private Plugins $plugins,
        private Utilities $utilities,
        private GeneralConfig $generalConfig,
        private ElementSources $elementSources,
        private Settings $settings,
    ) {}

    /**
     * The nav for the current request, with badge counts and
     * the selected trail applied.
     *
     * @return NavItem[]
     */
    public function getItems(): array
    {
        return $this->applySelection($this->applyBadgeCounts($this->getTree()));
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
     * The whole navigation tree, without selection or badge counts.
     *
     * @return NavItem[]
     */
    public function getTree(): array
    {
        return $this->buildTree();
    }

    /**
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
            $entryPages = $this->elementSources->getPages(Entry::class, ElementSources::CONTEXT_NAVIGATION);

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

        // Add any Plugin nav items
        $pluginNavItems = collect();

        foreach ($this->plugins->getAllPlugins() as $plugin) {
            if (! $plugin->hasCpSection) {
                continue;
            }

            if (! Gate::check('accessPlugin-'.$plugin->handle)) {
                continue;
            }

            $pluginNavItem = $plugin->getCpNavItem();

            if ($pluginNavItem === null) {
                continue;
            }

            if (! $pluginNavItem instanceof NavItem) {
                $pluginNavItem = new NavItem($pluginNavItem);
            }

            $pluginNavItems->add($pluginNavItem);
        }

        // Ungrouped items come first, so a group's heading never ends up
        // heading the plugins listed after it.
        [$groupedPluginNavItems, $ungroupedPluginNavItems] = $pluginNavItems->partition(
            fn (NavItem $item): bool => $item->group,
        );

        $navItems = $navItems
            ->merge($ungroupedPluginNavItems)
            ->merge($groupedPluginNavItems);

        $administrationGroup = new NavItem()->label(t('Administration'))->group(true);
        $administrationItems = [];

        if (Edition::get() !== Edition::Solo && Gate::check('viewUsers')) {
            $administrationItems[] =
                new NavItem()
                    ->label(t('Users'))
                    ->href('users')
                    ->icon('user-group')
                    ->subnav($this->sourceSubnav(User::class, 'users'));
        }

        if ($isAdmin && $this->generalConfig->enableGql) {
            $subNavItems = collect()
                ->when(
                    $this->generalConfig->allowAdminChanges,
                    fn (Collection $subNavItems) => $subNavItems->add(
                        new NavItem()
                            ->label(t('Schemas'))
                            ->href(cp_url('graphql/schemas')),
                    ),
                )
                ->add(
                    new NavItem()
                        ->label(t('Tokens'))
                        ->href(cp_url('graphql/tokens')),
                )
                ->add(
                    new NavItem()
                        ->label('GraphiQL')
                        ->href(cp_url('graphql/explore')),
                );

            $administrationItems[] =
                new NavItem()
                    ->label('GraphQL')
                    ->href('graphql')
                    ->icon('custom-icons/graphql')
                    ->subnav($subNavItems->all());
        }

        $utilities = $this->utilities->getAuthorizedUtilityTypes();

        if ($utilities->isNotEmpty()) {
            $administrationItems[] = new NavItem()
                ->label(t('Utilities'))
                ->href('utilities')
                ->icon('wrench')
                ->subnav($this->utilitiesSubnav($utilities));
        }

        if ($isAdmin) {
            $administrationItems[] =
                new NavItem()
                    ->label(t('Settings'))
                    ->href('settings')
                    ->icon(
                        $this->generalConfig->allowAdminChanges
                            ? 'gear'
                            : 'gear-slash',
                    )
                    ->subnav($this->settingsSubnav());

            $administrationItems[] =
                new NavItem()
                    ->label(t('Plugin Store'))
                    ->href('plugin-store')
                    ->icon('plug');
        }

        if (! empty($administrationItems)) {
            $administrationGroup->subnav($administrationItems);
            $navItems->add($administrationGroup);
        }

        event($event = new CpNavItemsResolving($navItems->all()));

        return collect($event->navItems)
            ->map($this->normalize(...))
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
    private function sourceSubnav(
        string $elementType,
        string $indexUri,
        ?string $page = null,
    ): array {
        $items = [];
        $group = null;

        // Scoped to the site the CP is working with, so switching sites leaves
        // the nav listing only the sources that run on the new one.
        $sources = $this->elementSources->getSources(
            $elementType,
            context: ElementSources::CONTEXT_NAVIGATION,
            page: $page,
            siteId: $this->navSiteId(),
        );

        foreach ($sources as $source) {
            if (($source['type'] ?? null) === ElementSources::TYPE_HEADING) {
                $heading = (string) ($source['heading'] ?? '');

                // A blank heading separates a trailing run of un-configured
                // sources rather than naming one, so it closes the open group
                // instead of starting an empty one.
                $group =
                    $heading === ''
                        ? null
                        : new NavItem()
                            ->label($heading)
                            ->group(true)
                            ->subnav([]);

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
                ->href(
                    $this->sourceUri($elementType, $indexUri, $source, $page),
                );

            if ($group !== null) {
                $group->subnav = [...$group->subnav, $item];

                continue;
            }

            $items[] = $item;
        }

        // A heading whose members all turned out to be unreachable would
        // otherwise be left standing over nothing.
        return array_values(
            array_filter(
                $items,
                fn (NavItem $item): bool => ! $item->group ||
                    $item->subnav !== [],
            ),
        );
    }

    /**
     * The URL the nav links a source by — its own path where the element type
     * gives it one, the `?source=` query otherwise. Null for a source the nav
     * doesn't list, or an element type it has no index for.
     *
     * For landing somewhere after the sources change: it's the link the nav
     * will highlight, on whichever index page the source now lives.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function sourceUrl(string $elementType, string $key): ?string
    {
        foreach ($this->sourceIndexes($elementType) as [$indexUri, $page]) {
            $source = collect(
                $this->elementSources->getSources(
                    $elementType,
                    context: ElementSources::CONTEXT_NAVIGATION,
                    page: $page,
                    siteId: $this->navSiteId(),
                ),
            )->first(
                fn (array $source): bool => ($source['key'] ?? null) === $key,
            );

            if ($source !== null) {
                return Url::cpUrl(
                    $this->sourceUri($elementType, $indexUri, $source, $page),
                );
            }
        }

        return null;
    }

    /**
     * The indexes the nav lists an element type's sources under, as
     * `[index URI, page]` — one per page for entries, as {@see buildTree()}
     * lays them out.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return list<array{0: string, 1: string|null}>
     */
    private function sourceIndexes(string $elementType): array
    {
        if ($elementType === Entry::class) {
            $pages = $this->elementSources->getPages(
                Entry::class,
                ElementSources::CONTEXT_NAVIGATION,
            );

            return $pages->isEmpty()
                ? [['content/entries', null]]
                : $pages
                    ->map(
                        fn (string $page) => [
                            sprintf('content/%s', Str::slug($page)),
                            $page,
                        ],
                    )
                    ->values()
                    ->all();
        }

        return match ($elementType) {
            Asset::class => [['assets', null]],
            User::class => [['users', null]],
            default => [],
        };
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     */
    private function sourceUri(
        string $elementType,
        string $indexUri,
        array $source,
        ?string $page,
    ): string {
        return $elementType::sourceCpUri($source, $page) ??
            $this->sourceQueryUri($indexUri, (string) $source['key']);
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
            ->map(
                fn (string $class) => new NavItem()
                    ->label($class::displayName())
                    ->href('utilities/'.$class::id())
                    ->icon($class::icon()),
            )
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
            ->icon($this->solidIconName($setting['iconName'] ?? null))
            ->iconSvg($setting['icon'] ?? null);
    }

    /**
     * Settings name the light icons drawn large on the settings index; the
     * nav draws the solid ones, where they exist.
     */
    private function solidIconName(?string $icon): ?string
    {
        if ($icon === null || ! str_starts_with($icon, 'light/')) {
            return $icon;
        }

        $name = substr($icon, strlen('light/'));

        return is_file(CmsAssets::resourcesPath("icons/solid/$name.svg"))
            ? $name
            : $icon;
    }

    /**
     * Gives an item its id and an absolute URL, renders an icon it can't name,
     * and does the same for anything beneath it.
     */
    private function normalize(NavItem $item): NavItem
    {
        $item->id ??= $this->itemId((string) $item->href);

        if ($item->href !== null && $item->href !== '') {
            $item->href = Url::cpUrl($item->href);
        }

        $this->resolveIcon($item);

        if (is_array($item->subnav)) {
            $item->subnav = array_map($this->normalize(...), $item->subnav);
        }

        return $item;
    }

    /**
     * Renders an icon the control panel can't look up by name.
     *
     * The nav draws a named icon through `craft-nav-item`'s `icon` attribute
     * and anything else as inline markup, so those are the only two shapes it
     * understands. A plugin supplies neither: `cpNavIconPath()` returns the
     * path to its own `icon-mask.svg`, which went into `icon` and was then
     * looked up as though it were a system icon's name — so no plugin's icon
     * ever appeared.
     *
     * Rendering it here covers plugins written for Craft 6 and those coming
     * through the Yii adapter alike, since both arrive as a path in the same
     * field, and neither has to change.
     *
     * Deliberately narrow: only a value naming an SVG file is rendered, so the
     * named icons the rest of the nav uses stay names. Inlining every icon
     * would weigh down the tree sent with every control panel page.
     */
    private function resolveIcon(NavItem $item): void
    {
        if ($item->iconSvg !== null || $item->icon === null) {
            return;
        }

        if (! str_ends_with(strtolower($item->icon), '.svg')) {
            return;
        }

        $svg = Icons::svg($item->icon);

        // `Icons::svg()` logs and returns an empty string for anything it
        // can't read, so a missing or unreadable file leaves the item as it
        // was rather than giving it a blank icon.
        if ($svg !== null && $svg !== '') {
            $item->iconSvg = $svg;
            $item->icon = null;
        }
    }

    /** The stable id an item is known by, in the tree and in the badge map. */
    private function itemId(string $href): string
    {
        return 'nav-'.
            preg_replace(
                "/[^\w\-_]/",
                '',
                Str::ascii(str_replace('/', '-', $href)),
            );
    }

    /**
     * Marks the trail to the most specific matching item.
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

        $best = null;
        $bestLength = -1;
        $bestQuerySize = -1;
        $findBest = function (array $items) use (
            &$findBest,
            $path,
            &$best,
            &$bestLength,
            &$bestQuerySize,
        ): void {
            foreach ($items as $item) {
                if (is_array($item->subnav)) {
                    $findBest($item->subnav);
                }

                $itemPath = $this->navItemPath((string) $item->href);
                parse_str(
                    (string) parse_url((string) $item->href, PHP_URL_QUERY),
                    $params,
                );
                $querySize = count($params);

                if (
                    $this->pathMatches($path, $itemPath) &&
                    $this->queryMatches((string) $item->href) &&
                    ($bestLength < strlen($itemPath) ||
                        ($bestLength === strlen($itemPath) &&
                            $querySize > $bestQuerySize))
                ) {
                    $best = $item;
                    $bestLength = strlen($itemPath);
                    $bestQuerySize = $querySize;
                }
            }
        };

        $findBest($items);

        return $this->selectWithin($items, $path, $best);
    }

    /**
     * @param  NavItem[]  $items
     * @return NavItem[]
     */
    private function selectWithin(
        array $items,
        string $path,
        ?NavItem $best,
    ): array {
        foreach ($items as $item) {
            if (is_array($item->subnav)) {
                $item->subnav = $this->selectWithin(
                    $item->subnav,
                    $path,
                    $best,
                );
            }

            $descendantSelected =
                is_array($item->subnav) &&
                array_any(
                    $item->subnav,
                    fn (NavItem $child): bool => $child->selected,
                );

            $itemPath = $this->navItemPath((string) $item->href);

            if ($descendantSelected || $item === $best) {
                $item->selected = true;
                $item->linkAttributes['aria']['current'] =
                    $itemPath === $path ? 'page' : 'true';
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
     * The site the nav's sources are scoped to.
     *
     * `null` on a single-site install, or before a user can be asked which
     * sites they may edit — in both cases there's nothing to filter by.
     */
    public function navSiteId(): ?int
    {
        if (! Sites::isMultiSite()) {
            return null;
        }

        return app(RequestedSite::class)->get()?->id;
    }

    private function navItemPath(string $url): string
    {
        return Url::stripCpTrigger(
            rawurldecode((string) parse_url($url, PHP_URL_PATH)),
        );
    }

    /**
     * Whether the request carries every query parameter the item's URL does.
     * An item addressed by query (`assets?source=temp`) shares its path with
     * the bare index, so the path alone would claim that page for it.
     */
    private function queryMatches(string $url): bool
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);

        return array_all(
            $params,
            fn ($value, $name) => ! ($this->request->query($name) !== $value),
        );
    }

    private function pathMatches(string $path, string $itemPath): bool
    {
        return $itemPath !== '' &&
            ($path === $itemPath || str_starts_with($path, $itemPath.'/'));
    }
}

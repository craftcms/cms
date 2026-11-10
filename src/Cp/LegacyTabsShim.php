<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Cp\Components\NavItem;
use CraftCms\Cms\Cp\Components\NavList;
use CraftCms\Cms\Cp\Components\Tab;
use CraftCms\Cms\Cp\Components\Tabs;
use CraftCms\Cms\Cp\Enums\NavAppearance;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

/**
 * Renders a legacy screen's tabs as a Craft 6 component, with no plugin changes.
 *
 * A legacy screen declares tabs as a config array of labels and urls, and the
 * url says which of two very different things the strip is:
 *
 * - A local anchor — `['label' => 'API Connection', 'url' => '#api']` — names a
 *   pane the screen renders inside its own content. That is a tab strip, and
 *   `<craft-tabs>` has a mode built for exactly that shape: give each tab a
 *   `controls` naming its pane's id and the strip drives those panes where they
 *   already are, toggling Craft's `hidden` class. That is the contract the
 *   legacy strip already used, so the content needs no rewriting and nothing
 *   moves in the DOM — which matters, because form inputs that move out of the
 *   page form stop posting.
 * - A url — `['label' => 'Logs', 'url' => '/admin/feed-me/logs']` — is another
 *   page. Those aren't tabs: nothing is revealed, the browser leaves. So they
 *   render as the navigation they are, a `<craft-nav-list>` of links drawn as a
 *   strip, which looks like a tab row without claiming the tabs pattern.
 *
 * Each mode is all-or-nothing, so a strip mixing the two returns `null` and the
 * caller falls back to the legacy strip.
 *
 * @deprecated Exists only while legacy screens render inside the Inertia shell.
 *
 * @internal
 *
 * @since 6.0.0
 */
final class LegacyTabsShim
{
    /**
     * Builds the strip for a screen's tabs.
     *
     * @param  array<array-key, array<string, mixed>>  $tabs  The screen's tab config.
     * @param  array-key|null  $selectedTab  Key of the tab the screen is on, as
     *                                       `_includes/tabs` takes it. Defaults
     *                                       to the first.
     * @param  string|null  $label  The screen's title, which names the strip
     *                              when it turns out to be a nav.
     * @return string|null The rendered strip, or null to keep the legacy one.
     */
    public static function apply(
        array $tabs,
        string|int|null $selectedTab = null,
        ?string $label = null,
    ): ?string {
        if (count($tabs) < 2) {
            return null;
        }

        $labels = [];
        $urls = [];

        foreach ($tabs as $key => $tab) {
            $url = $tab['url'] ?? null;
            $text = $tab['label'] ?? null;

            if (! is_string($url) || $url === '' || ! is_string($text)) {
                return null;
            }

            $labels[$key] = $text;
            $urls[$key] = $url;
        }

        $anchors = array_filter($urls, fn (string $url): bool => str_starts_with($url, '#'));

        if (count($anchors) === count($urls)) {
            return self::panes($tabs, $labels, $urls, $selectedTab);
        }

        if ($anchors === []) {
            return self::pages($tabs, $labels, $urls, $selectedTab, $label);
        }

        return null;
    }

    /**
     * Tabs over panes the screen renders itself.
     *
     * @param  array<array-key, array<string, mixed>>  $tabs
     * @param  array<array-key, string>  $labels
     * @param  array<array-key, string>  $urls
     */
    private static function panes(
        array $tabs,
        array $labels,
        array $urls,
        string|int|null $selectedTab,
    ): ?string {
        // The same name the legacy strip gives its tab list.
        $component = Tabs::make()
            ->label(t('Primary fields'))
            ->selectedIndex(self::selectedIndex($urls, $selectedTab));

        foreach ($urls as $key => $url) {
            $id = substr($url, 1);

            if ($id === '') {
                return null;
            }

            $component = $component->tab(
                Tab::make()
                    ->label($labels[$key])
                    ->icon(is_string($tabs[$key]['icon'] ?? null) ? $tabs[$key]['icon'] : null)
                    ->controls($id),
            );
        }

        return $component->toHtml();
    }

    /**
     * Links to the screen's sibling pages, drawn as a strip.
     *
     * Wrapped in a named landmark, which is what makes a nav findable — the
     * list supplies the list semantics and nothing more. The name is the
     * screen's own, since the pages the strip moves between are its.
     *
     * @param  array<array-key, array<string, mixed>>  $tabs
     * @param  array<array-key, string>  $labels
     * @param  array<array-key, string>  $urls
     */
    private static function pages(
        array $tabs,
        array $labels,
        array $urls,
        string|int|null $selectedTab,
        ?string $label,
    ): string {
        $selected = self::selectedIndex($urls, $selectedTab);
        $component = NavList::make()->appearance(NavAppearance::Tabs);

        foreach (array_keys($urls) as $position => $key) {
            $component = $component->item(
                NavItem::make()
                    ->label($labels[$key])
                    ->icon(is_string($tabs[$key]['icon'] ?? null) ? $tabs[$key]['icon'] : null)
                    ->url($urls[$key])
                    ->current($position === $selected),
            );
        }

        return Html::tag('nav', $component->toHtml(), [
            'aria-label' => $label !== null && trim($label) !== '' ? trim($label) : t('Pages'),
        ]);
    }

    /**
     * Which tab the screen is on, as a position in the strip. An unknown key
     * lands on the first tab, the way `_includes/tabs` reads a missing one.
     *
     * @param  array<array-key, string>  $urls
     */
    private static function selectedIndex(array $urls, string|int|null $selectedTab): int
    {
        if ($selectedTab === null) {
            return 0;
        }

        $position = array_search($selectedTab, array_keys($urls), true);

        return $position === false ? 0 : $position;
    }
}

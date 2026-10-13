<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Cp\Components\Tab;
use CraftCms\Cms\Cp\Components\Tabs;

use function CraftCms\Cms\t;

/**
 * Renders a legacy screen's tabs as `<craft-tabs>`, with no plugin changes.
 *
 * A legacy screen declares tabs as a config array pointing at anchors —
 * `['label' => 'API Connection', 'url' => '#api']` — and renders the matching
 * panes inside its own content. `<craft-tabs>` has a mode built for exactly
 * that shape: give each tab a `controls` naming its pane's id, and the strip
 * drives those panes where they already are, toggling Craft's `hidden` class.
 *
 * That is the contract the legacy tab strip already used, so the content needs
 * no rewriting and nothing moves in the DOM — which matters, because form
 * inputs that move out of the page form stop posting.
 *
 * The mode is all-or-nothing: a strip is entirely `controls` or entirely
 * slotted, never a mix. So a single tab pointing somewhere other than a local
 * anchor returns `null`, and the caller falls back to the legacy strip.
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
     * Builds the tab strip for a screen whose tabs point at panes in its content.
     *
     * @param  array<array-key, array<string, mixed>>  $tabs  The screen's tab config.
     * @return string|null The rendered strip, or null to keep the legacy one.
     */
    public static function apply(array $tabs): ?string
    {
        if (count($tabs) < 2) {
            return null;
        }

        // The same name the legacy strip gives its tab list.
        $component = Tabs::make()->label(t('Primary fields'))->selectedIndex(0);

        foreach ($tabs as $tab) {
            $url = $tab['url'] ?? null;
            $label = $tab['label'] ?? null;

            if (! is_string($url) || ! is_string($label) || ! str_starts_with($url, '#')) {
                return null;
            }

            $id = substr($url, 1);

            if ($id === '') {
                return null;
            }

            $component = $component->tab(
                Tab::make()->label($label)->controls($id),
            );
        }

        return $component->toHtml();
    }
}

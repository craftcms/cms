<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use CraftCms\Cms\Cp\Data\NavItem;

/**
 * @event CpNavItemsResolving The event that is triggered when registering control panel nav items.
 *
 * ```php
 * use CraftCms\Cms\Cp\Events\CpNavItemsResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(
 *     function(CpNavItemsResolving $e) {
 *         $e->navItems[] = new NavItem()
 *             ->label('Item Label')
 *             ->url('my-module')
 *             ->icon('/path/to/icon.svg');
 *     }
 * );
 * ```
 *
 * [[CpNavItemsResolving::$navItems]] is an array whose values are sub-arrays that define the nav items. Each sub-array can have the following keys:
 *
 * - `label` – The item’s label.
 * - `url` – The URL or path of the control panel page the item should link to.
 * - `icon` – The path to the SVG icon that should be used for the item.
 * - `badgeCount` _(optional)_ – The badge count number that should be displayed next to the label.
 * - `external` _(optional)_ – Set to `true` if the item links to an external URL.
 * - `id` _(optional)_ – The ID of the `<li>` element. If not specified, it will default to `nav-`.
 * - `subnav` _(optional)_ – A nested array of sub-navigation items that should be displayed if the main item is selected.
 *
 *   The keys of the array should define the items’ IDs, and the values should be nested arrays with `label` and `url` keys, and optionally
 *   `badgeCount` and `external` keys.
 *
 * If a subnav is defined, subpages can specify which subnav item should be selected by defining a `selectedSubnavItem` variable that is set to
 * the selected item’s ID (its key in the `subnav` array).
 */
class CpNavItemsResolving
{
    public function __construct(
        /** @var NavItem[] */
        public array $navItems,
    ) {}
}

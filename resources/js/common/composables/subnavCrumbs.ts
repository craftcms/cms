import {navItemActions} from '@/common/composables/navActions';
import {
  navItemContains,
  withNavSelection,
} from '@/common/composables/navSelection';
import type {BreadcrumbItem} from '@/common/types';

type NavItem = CraftCms.Cms.Cp.Data.NavItem;

/**
 * Turns a secondary nav into breadcrumbs.
 *
 * The nav already says where you are — one of its items is selected — but
 * that's only visible while the nav is on screen, and it collapses below the
 * large breakpoint. Restating the trail in the breadcrumbs keeps the answer in
 * one predictable place, and gives each level the same switcher menu an
 * element index's source crumb has: the items alongside this one, so you can
 * move between them without opening the nav.
 */
export function subnavCrumbs(items: Array<NavItem>): Array<BreadcrumbItem> {
  const crumbs: Array<BreadcrumbItem> = [];
  let level = items;

  while (level.length > 0) {
    const selected = level.find((item) => item.selected);

    if (!selected) {
      break;
    }

    // A group is a heading over its children rather than somewhere to go, so
    // it names its level without linking anywhere — but it still belongs in
    // the menu, as the heading it is in the nav.
    const choices = level.filter((item) => !item.group).length;

    crumbs.push({
      label: selected.label,
      href: selected.group ? null : selected.href,
      // One option is no choice at all, so it gets a plain crumb.
      ...(choices > 1 || level.some((item) => item.group)
        ? {items: navItemActions(level)}
        : {}),
    });

    level = Array.isArray(selected.subnav) ? selected.subnav : [];
  }

  return crumbs;
}

/**
 * Folds a nav's trail into the crumbs a page supplied.
 *
 * An index screen writes its own last crumb, and it's the selected nav item by
 * another name — so the derived one takes its place rather than repeating it,
 * bringing the switcher with it. Screens whose trail ends somewhere else (a
 * user's account screens end in a chip for the user) get the nav crumb added
 * after it, which is the level they were missing.
 */
export function withSubnavCrumbs(
  crumbs: Array<BreadcrumbItem>,
  subnav: Array<NavItem>
): Array<BreadcrumbItem> {
  const derived = subnavCrumbs(subnav);

  if (derived.length === 0) {
    return crumbs;
  }

  const last = crumbs.at(-1);
  const overlaps = last != null && last.label === derived[0]!.label;

  return [...(overlaps ? crumbs.slice(0, -1) : crumbs), ...derived];
}

function samePath(one: string | null, other: string): boolean {
  return (
    one !== null && navItemContains(one, other) && navItemContains(other, one)
  );
}

/**
 * The levels below this one, as the nav draws them.
 *
 * A group heading isn't a level of its own — its children belong to the level
 * the heading sits in — so only what hangs off *them* counts as deeper.
 */
function levelsBelow(items: Array<NavItem>): Array<Array<NavItem>> {
  return items.flatMap((item) => {
    const children = Array.isArray(item.subnav) ? item.subnav : [];

    if (item.group) {
      return levelsBelow(children);
    }

    return children.length > 0 ? [children] : [];
  });
}

/**
 * The list in the nav holding an item that links to `href`, looking through
 * group headings the way the nav draws them.
 */
function navLevelOf(
  items: Array<NavItem>,
  href: string
): Array<NavItem> | null {
  const holds = (item: NavItem): boolean =>
    samePath(item.href, href) ||
    (item.group && Array.isArray(item.subnav) && item.subnav.some(holds));

  // Deeper levels answer first, the way selection resolves the same ambiguity
  // (see `selectWithin`). An element index shares its URL with the “all
  // elements” source listed beneath it, so checking this level first handed
  // the index's own crumb the main nav instead of its sources.
  for (const level of levelsBelow(items)) {
    const found = navLevelOf(level, href);

    if (found) {
      return found;
    }
  }

  if (items.some(holds)) {
    return items;
  }

  return null;
}

/**
 * Gives each crumb that has a switcher the menu the nav draws for its level.
 *
 * An index screen's crumbs come from its secondary nav, while a screen deeper
 * in (an entry's edit page, say) gets its crumbs from the server. Taking the
 * menu from the nav for both keeps a source's switcher the same wherever it
 * appears. A crumb without a menu keeps not having one, and one the nav
 * doesn't know keeps the server's.
 */
export function withNavCrumbMenus(
  crumbs: Array<BreadcrumbItem>,
  nav: Array<NavItem>
): Array<BreadcrumbItem> {
  return crumbs.map((crumb) => {
    const href = crumb.href ?? crumb.url;

    if (!crumb.items?.length || !href) {
      return crumb;
    }

    const level = navLevelOf(withNavSelection(nav, href), href);

    return level ? {...crumb, items: navItemActions(level)} : crumb;
  });
}

import {navItemActions} from '@/common/composables/navActions';
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

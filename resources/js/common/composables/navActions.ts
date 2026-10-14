import type {
  ActionItemButton,
  ActionItemGroup,
  ActionItemLink,
} from '@/common/types';

type NavItem = CraftCms.Cms.Cp.Data.NavItem;

/**
 * Describes nav items as action descriptors.
 *
 * A nav gets shown as a menu in more than one place — the switcher on a
 * breadcrumb, and the secondary nav's own menu once it collapses — and those
 * should be the same menu, not two that drifted. Mapping once here is what
 * keeps the checkmark on the current item and the icons in both.
 *
 * The whole tree comes across, children and all. A menu draws a flat list and
 * ignores the children; a nav draws them. What neither has to do is go back to
 * a second, differently-shaped description of the same nav to find them.
 */
export function navItemActions(
  items: Array<NavItem>,
  nested = false
): Array<ActionItemLink | ActionItemButton | ActionItemGroup> {
  return items.map((item) =>
    // A group heads its children rather than being somewhere to go. It becomes
    // a heading over them, which is how the nav itself draws it, so the menu
    // reads the same as the list.
    item.group && Array.isArray(item.subnav)
      ? {
          type: 'group',
          heading: item.label ?? undefined,
          items: item.subnav.map((child) => navItemAction(child, nested)),
        }
      : navItemAction(item, nested)
  );
}

/**
 * A badge is a dot on a top-level item, and a number on one nested under
 * another — an element index source, counting what it lists.
 */
export function navItemAction(
  item: NavItem,
  nested = false
): ActionItemLink | ActionItemButton {
  const children = Array.isArray(item.subnav) ? item.subnav : [];
  const shared = {
    label: item.label ?? '',
    selected: item.selected,
    ...(item.icon ? {icon: item.icon} : {}),
    ...(item.iconSvg ? {iconSvg: item.iconSvg} : {}),
    ...(item.status ? {status: item.status} : {}),
    ...(item.badgeCount > 0
      ? nested
        ? {count: item.badgeCount}
        : {indicator: true}
      : {}),
    ...(children.length > 0 ? {subnav: navItemActions(children, true)} : {}),
  };

  // Not everything in a nav is a destination — a heading isn't, and neither is
  // an entry whose URL the server left off. It still belongs in the list, just
  // not as something to follow.
  if (!item.href) {
    return {...shared, disabled: true};
  }

  return {
    type: 'link',
    href: item.href,
    ...shared,
    ...(item.external ? {external: true} : {}),
  };
}

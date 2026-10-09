type NavNode = CraftCms.Cms.Cp.Data.ActionItem;

/**
 * Marks the trail from the nav's root to the page you're on.
 *
 * The server used to do this, but the nav tree is now handed over once and
 * kept — it's the same on every page, so re-sending it with each response was
 * pure weight. Selection is the one part of it that isn't the same on every
 * page, so it moved here.
 *
 * Mirrors `Cp\Navigation::applySelection()`: the most specific matching item
 * claims its ancestors, even when a shorter match is in an earlier group.
 */

/** Compares pathnames, so a relative href and an absolute one still match. */
function pathOf(href: string | null): string {
  if (!href) {
    return '';
  }

  try {
    return new URL(href, window.location.origin).pathname.replace(/\/+$/, '');
  } catch {
    return '';
  }
}

function matches(path: string, itemPath: string): boolean {
  return (
    itemPath !== '' && (path === itemPath || path.startsWith(`${itemPath}/`))
  );
}

function queryOf(href: string | null): URLSearchParams {
  try {
    return new URL(href ?? '', window.location.origin).searchParams;
  } catch {
    return new URLSearchParams();
  }
}

/**
 * Whether the URL carries every query parameter the item's href does. An item
 * addressed by query (`assets?source=temp`) shares its path with the bare
 * index, so the path alone would claim that page for it.
 *
 * `site` is left out: on a multi-site install the server stamps it onto every
 * CP URL, so it says which site you're working in rather than where an item
 * is. Requiring it would leave a URL built without it — an index's own route,
 * say — belonging to no item at all.
 */
function queryMatches(
  query: URLSearchParams,
  itemHref: string | null
): boolean {
  return [...queryOf(itemHref)].every(
    ([name, value]) => name === 'site' || query.get(name) === value
  );
}

/**
 * Whether a nav item's href is, or is an ancestor of, the given URL — the rule
 * selection uses, for anything else that needs the item a page belongs to.
 */
export function navItemContains(itemHref: string | null, url: string): boolean {
  return (
    matches(pathOf(url), pathOf(itemHref)) &&
    queryMatches(queryOf(url), itemHref)
  );
}

/** `subnav` is `false` when the server hasn't resolved a branch yet. */
function childrenOf(item: NavNode): Array<NavNode> {
  return Array.isArray(item.subnav) ? item.subnav : [];
}

function selectWithin(
  items: Array<NavNode>,
  best: NavNode | null
): Array<NavNode> {
  return items.map((item) => {
    const children = childrenOf(item);
    const subnav = children.length ? selectWithin(children, best) : item.subnav;
    const selected =
      item === best ||
      (Array.isArray(subnav) && subnav.some((child) => child.selected));

    return {...item, selected, subnav};
  });
}

/**
 * The tree with `selected` set against `url`.
 *
 * Returns new objects rather than mutating: the tree is a once-prop the client
 * holds onto across visits, so writing selection into it would leave the
 * previous page's trail behind on the next one.
 */
export function withNavSelection(
  items: Array<NavNode>,
  url: string
): Array<NavNode> {
  let path = pathOf(url);

  // Your own account sits under Users, which is where the nav points.
  path = path.replace(/\/myaccount(\/|$)/, '/users$1');

  const query = queryOf(url);
  let best: NavNode | null = null;
  let bestLength = -1;
  let bestQuerySize = -1;

  function findBest(nodes: Array<NavNode>) {
    for (const item of nodes) {
      findBest(childrenOf(item));

      const itemPath = pathOf(item.href);
      const querySize = queryOf(item.href).size;

      if (
        matches(path, itemPath) &&
        queryMatches(query, item.href) &&
        (itemPath.length > bestLength ||
          (itemPath.length === bestLength && querySize > bestQuerySize))
      ) {
        best = item;
        bestLength = itemPath.length;
        bestQuerySize = querySize;
      }
    }
  }

  findBest(items);

  return selectWithin(items, best);
}

/**
 * Fills in the counts the tree deliberately doesn't carry.
 *
 * Badge counts change without the nav's shape changing — pending migrations,
 * available updates — so they're the one part that can't be cached with it.
 */
export function withNavBadges(
  items: Array<NavNode>,
  badges: Record<string, number>
): Array<NavNode> {
  if (Object.keys(badges).length === 0) {
    return items;
  }

  return items.map((item) => {
    const children = childrenOf(item);

    return {
      ...item,
      badgeCount: (item.id && badges[item.id]) || item.badgeCount,
      subnav: children.length ? withNavBadges(children, badges) : item.subnav,
    };
  });
}

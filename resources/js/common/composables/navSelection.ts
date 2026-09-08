type NavNode = CraftCms.Cms.Cp.Data.ActionItem;

/**
 * Marks the trail from the nav's root to the page you're on.
 *
 * The server used to do this, but the nav tree is now handed over once and
 * kept — it's the same on every page, so re-sending it with each response was
 * pure weight. Selection is the one part of it that isn't the same on every
 * page, so it moved here.
 *
 * Mirrors `Cp\Navigation::applySelection()`: a descendant claims its
 * ancestors, and failing that the first item whose path prefixes the current
 * one wins.
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

/** `subnav` is `false` when the server hasn't resolved a branch yet. */
function childrenOf(item: NavNode): Array<NavNode> {
  return Array.isArray(item.subnav) ? item.subnav : [];
}

function selectWithin(
  items: Array<NavNode>,
  path: string,
  state: {found: boolean}
): Array<NavNode> {
  // Every child first, so a deeper match settles the question before any item
  // at this level gets to answer it.
  const resolved = items.map((item) => {
    const children = childrenOf(item);
    const subnav = children.length
      ? selectWithin(children, path, state)
      : item.subnav;

    return {
      item,
      subnav,
      descendantSelected:
        Array.isArray(subnav) && subnav.some((child) => child.selected),
    };
  });

  // The longest match among siblings, not the first.
  //
  // An index sits alongside the sources beneath it — `content/entries` next to
  // `content/entries/blog` — and prefixes every one of them. First-match-wins
  // handed it the selection on every source page, and the source you were
  // actually on never lit up.
  let best = -1;
  let bestLength = -1;

  if (!state.found) {
    resolved.forEach(({item}, index) => {
      const itemPath = pathOf(item.href);

      if (matches(path, itemPath) && itemPath.length > bestLength) {
        best = index;
        bestLength = itemPath.length;
      }
    });
  }

  return resolved.map(({item, subnav, descendantSelected}, index) => {
    // A selected descendant claims its ancestors unconditionally — the trail
    // has to reach the root.
    const selected = descendantSelected || index === best;

    if (selected) {
      state.found = true;
    }

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

  return selectWithin(items, path, {found: false});
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

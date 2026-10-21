/**
 * Working out where focus goes when an overlay closes.
 *
 * The element that opened a dialog or slideout isn't always there to take focus
 * back. An item in an action menu is hidden once the menu closes, and anything
 * can be re-rendered while the overlay is open. {@link focusReturnFor} records
 * how to find the target again and resolves it only when it's needed.
 */

const MENU_SELECTOR = 'craft-action-menu, craft-popover';

/** Matched against the menu or popover to find it again after a re-render. */
export const FOCUS_KEY_ATTRIBUTE = 'data-focus-key';

/** Limits a {@link FOCUS_KEY_ATTRIBUTE} lookup to one region, such as a panel. */
export const FOCUS_SCOPE_ATTRIBUTE = 'data-focus-scope';

/** Resolves where focus should go, or `null` to leave it to the caller. */
export type FocusReturnResolver = () => HTMLElement | null;

/**
 * Whether `el` can take focus right now.
 *
 * Unlike `isVisible()` in `utilities/dom`, a transparent element still counts,
 * since it can still be focused; and unlike the focus trap's tabbable check, so
 * does one with `tabindex="-1"`, such as a panel focused programmatically.
 */
export function isFocusable(el: Element | null | undefined): el is HTMLElement {
  if (!(el instanceof HTMLElement) || !el.isConnected || el === document.body) {
    return false;
  }

  if (closestComposed(el, '[inert]') || el.matches(':disabled')) {
    return false;
  }

  return typeof el.checkVisibility === 'function'
    ? el.checkVisibility({visibilityProperty: true})
    : !closestComposed(el, '[hidden]');
}

/** `closest()` that carries on past shadow roots to their hosts. */
function closestComposed(el: Element, selector: string): HTMLElement | null {
  let node: Element | null = el;

  while (node) {
    const match = node.closest<HTMLElement>(selector);

    if (match) {
      return match;
    }

    const root = node.getRootNode();
    node = root instanceof ShadowRoot ? root.host : null;
  }

  return null;
}

function invokerOf(menu: Element): HTMLElement | null {
  return menu.querySelector<HTMLElement>(':scope > [slot="invoker"]');
}

/**
 * The menus and popovers whose content holds `el`, nearest first. A menu's own
 * invoker isn't part of its content.
 */
function menusContaining(el: Element): HTMLElement[] {
  const menus: HTMLElement[] = [];
  let node: Element = el;
  let menu = closestComposed(node, MENU_SELECTOR);

  while (menu) {
    if (!invokerOf(menu)?.contains(node)) {
      menus.push(menu);
    }

    node = menu;
    menu = menu.parentElement
      ? closestComposed(menu.parentElement, MENU_SELECTOR)
      : null;
  }

  return menus;
}

function scopeKeyOf(el: Element): string | null {
  return (
    closestComposed(el, `[${FOCUS_SCOPE_ATTRIBUTE}]`)?.getAttribute(
      FOCUS_SCOPE_ATTRIBUTE
    ) ?? null
  );
}

function findMenuByKey(key: string, scopeKey: string | null): HTMLElement[] {
  const keyed = MENU_SELECTOR.split(', ')
    .map((menu) => `${menu}[${FOCUS_KEY_ATTRIBUTE}]`)
    .join(', ');

  return [...document.querySelectorAll<HTMLElement>(keyed)].filter(
    (menu) =>
      menu.getAttribute(FOCUS_KEY_ATTRIBUTE) === key &&
      scopeKeyOf(menu) === scopeKey
  );
}

/**
 * Records where focus should go back to once whatever `trigger` opens closes.
 *
 * The returned resolver tries, in order:
 *
 * 1. `trigger` itself.
 * 2. The invoker of each action menu or popover `trigger` sat in, nearest
 *    first, so a closed submenu hands focus to the menu it was opened from.
 * 3. For each of those menus with a `data-focus-key`, the invoker of a menu
 *    with the same key inside the same `data-focus-scope` (or outside any
 *    scope). This finds the menu again after a re-render, without picking up a
 *    same-keyed menu in another panel.
 *
 * Each candidate has to be able to take focus. If none can, it returns `null`.
 */
export function focusReturnFor(
  trigger: Element | null | undefined
): FocusReturnResolver {
  if (!trigger) {
    return () => null;
  }

  const menus = menusContaining(trigger).map((menu) => ({
    menu,
    key: menu.getAttribute(FOCUS_KEY_ATTRIBUTE) || null,
    scopeKey: scopeKeyOf(menu),
  }));

  return () => {
    if (isFocusable(trigger)) {
      return trigger;
    }

    for (const {menu} of menus) {
      const invoker = menu.isConnected ? invokerOf(menu) : null;

      if (isFocusable(invoker)) {
        return invoker;
      }
    }

    for (const {key, scopeKey} of menus) {
      if (key === null) {
        continue;
      }

      for (const match of findMenuByKey(key, scopeKey)) {
        const replacement = invokerOf(match);

        if (isFocusable(replacement)) {
          return replacement;
        }
      }
    }

    return null;
  };
}

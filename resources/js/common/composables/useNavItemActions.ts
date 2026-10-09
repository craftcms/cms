import {createGlobalState} from '@vueuse/core';
import {onScopeDispose, reactive} from 'vue';
import {navItemContains} from '@/common/composables/navSelection';
import type {ActionItemButton} from '@/common/types';

interface LentAction {
  /** A URL inside the nav item the action belongs to — usually the page's own. */
  url: () => string;
  action: ActionItemButton;
}

/**
 * Actions a page lends to the nav item it lives under, for as long as the page
 * is mounted — the gear beside Entries that opens Customize Sources, say.
 *
 * Global rather than provided, because the nav isn't inside the page: the
 * sidebar renders it outside the page screen, so nothing a page provides can
 * reach it. Only the page knows the action applies, and only the nav knows
 * where to draw it.
 *
 * Meant for full pages. A slideout keeps the page beneath it mounted, so an
 * index registering from inside one would lend a second action alongside the
 * page's own.
 */
const useRegistry = createGlobalState(() =>
  reactive(new Map<symbol, LentAction>())
);

/**
 * Lends `action` to whichever nav item contains `url()`, until the calling
 * component unmounts. `url` is a getter so an Inertia visit that patches the
 * page rather than remounting it moves the action along with it.
 */
export function useNavItemAction(
  url: () => string,
  action: ActionItemButton
): void {
  const registry = useRegistry();
  const key = Symbol('nav-item-action');

  registry.set(key, {url, action});
  onScopeDispose(() => registry.delete(key));
}

/** Looks up the actions currently lent to the nav item at a given href. */
export function useNavItemActions(): (
  href: string | undefined
) => Array<ActionItemButton> {
  const registry = useRegistry();

  return (href) =>
    href
      ? [...registry.values()]
          .filter((lent) => navItemContains(href, lent.url()))
          .map((lent) => lent.action)
      : [];
}

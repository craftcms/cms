import {router} from '@inertiajs/vue3';
import {actionClient} from '@craftcms/ui';
import ElementIndexViewStateController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ElementIndexViewStateController';
import {prefixedKey} from '@/common/composables/useStorage';
import type {CraftData} from '@/common/composables/useCraftData';
import type {ViewState} from '@/modules/elements/types/view-state';

/**
 * The view states index pages render for: `elementindex.{elementType}.index`.
 * Modals, embedded indexes, and nested elements request their own rows.
 */
const VIEW_STATE_KEY = /^elementindex\.[\w\\]+\.index$/;

/** Whether a view state's local storage key is one the server renders for. */
export function isSyncedViewStateKey(key: string): boolean {
  return VIEW_STATE_KEY.test(key);
}

/**
 * Mirrors element index view states to the server for the session, so an
 * index page load (a nav prefetch included) comes with rows rendered for
 * them, rather than placeholders until the index has read local storage and
 * requested its rows.
 */
export function pushElementIndexViewStates(
  states: Record<string, ViewState>,
  all = false
): void {
  actionClient
    .post(ElementIndexViewStateController.url(), {states, all})
    .catch(() => {
      // The index requests its rows itself while the server doesn't know.
    });
}

/** Every element index view state in local storage, by unprefixed key. */
function storedViewStates(): Record<string, ViewState> {
  const prefix = prefixedKey('');
  const states: Record<string, ViewState> = {};

  for (const storageKey of Object.keys(localStorage)) {
    const key = storageKey.slice(prefix.length);

    if (!storageKey.startsWith(prefix) || !isSyncedViewStateKey(key)) continue;

    try {
      const state = JSON.parse(localStorage.getItem(storageKey) ?? '');

      if (state instanceof Object) {
        states[key] = state;
      }
    } catch {
      // Not a view state.
    }
  }

  return states;
}

/**
 * Pushes every view state in local storage the first time a page says the
 * session doesn't have them yet, such as after signing in.
 */
export function installElementIndexViewStateSync(): void {
  let pushed = false;

  router.on('navigate', (event) => {
    const craft = event.detail.page.props.craft as CraftData | undefined;
    const synced = craft?.currentUser?.elementIndexViewStatesSynced;

    if (synced === undefined) return;

    // A partial reload keeps the flag from the last full response, which may
    // predate the push.
    if (synced) {
      pushed = false;
      return;
    }

    if (pushed) return;

    pushed = true;
    pushElementIndexViewStates(storedViewStates(), true);
  });
}

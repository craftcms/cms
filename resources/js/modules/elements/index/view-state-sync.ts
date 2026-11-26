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

let pushes: Promise<boolean> = Promise.resolve(true);

/**
 * Mirrors element index view states to the server for the session, so an
 * index page load (a nav prefetch included) comes with rows rendered for
 * them, rather than placeholders until the index has read local storage and
 * requested its rows.
 *
 * Pushes go one at a time, so an older one can't land after a newer one and
 * put its state back. Once one lands, pages prefetched before it are dropped,
 * since they were rendered for the old state. Resolves to whether it landed;
 * until one does, the index requests its rows itself.
 */
export function pushElementIndexViewStates(
  states: Record<string, ViewState>,
  all = false
): Promise<boolean> {
  pushes = pushes
    .then(() =>
      actionClient.post(ElementIndexViewStateController.url(), {states, all})
    )
    .then(
      () => {
        router.flushAll();
        return true;
      },
      () => false
    );

  return pushes;
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
    // Retried on the next navigation if it fails.
    void pushElementIndexViewStates(storedViewStates(), true).then((landed) => {
      pushed = landed;
    });
  });
}

import {createGlobalState} from '@vueuse/core';
import {onScopeDispose, reactive} from 'vue';
import {router} from '@inertiajs/vue3';
import {openCustomizeSourcesModal} from '@/modules/customize-sources/open-customize-sources-modal';

interface CustomizeSourcesHandler {
  /** The element type whose sources the page edits itself. */
  elementType: () => string | null | undefined;
  open: () => void;
}

/**
 * Pages that open Customize Sources themselves, for as long as they're mounted.
 *
 * The nav footer's Customize menu offers every element type's sources from any
 * page. An index of that type opens its own modal instead, so the modal starts
 * on the index's page and source, and saving lands on the source last edited.
 *
 * Global rather than provided, because the sidebar renders outside the page
 * screen, so nothing a page provides can reach it.
 */
const useRegistry = createGlobalState(() =>
  reactive(new Map<symbol, CustomizeSourcesHandler>())
);

/**
 * Handles Customize Sources for `elementType()` until the calling component
 * unmounts. `elementType` is a getter so an Inertia visit that patches the
 * page rather than remounting it keeps the handler current.
 */
export function useCustomizeSourcesHandler(
  elementType: () => string | null | undefined,
  open: () => void
): void {
  const registry = useRegistry();
  const key = Symbol('customize-sources-handler');

  registry.set(key, {elementType, open});
  onScopeDispose(() => registry.delete(key));
}

/**
 * Opens Customize Sources for an element type: through the mounted page that
 * handles it, or on its own, reloading the page's props once saved so the
 * nav picks up the new sources.
 */
export function openCustomizeSources(elementType: string): void {
  const handler = [...useRegistry().values()].find(
    (handler) => handler.elementType() === elementType
  );

  if (handler) {
    handler.open();
    return;
  }

  void openCustomizeSourcesModal({
    elementType,
    onSaved: () =>
      new Promise<void>((resolve) =>
        router.reload({onFinish: () => resolve()})
      ),
  });
}

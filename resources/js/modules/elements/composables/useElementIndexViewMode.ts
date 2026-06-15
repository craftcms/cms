import {computed, onMounted, type Ref} from 'vue';
import {router} from '@inertiajs/vue3';
import {index} from '@/routes/craft/cp/content/index.js';
import type {ViewMode, ViewState} from '@/modules/elements/types/view-state';

interface ElementIndexViewModeContext {
  page: string;
  sectionHandle?: string | number;
}

/**
 * Server-driven view mode. Switching modes updates the local view state
 * immediately (so the toolbar + view react without waiting on the network),
 * then pushes a `viewMode` Inertia visit that reflects the change in the URL
 * and refreshes the server-rendered elements. `preserveState` keeps the
 * optimistic local state in place while the server responds.
 */
export function useElementIndexViewMode(
  props: ElementIndexViewModeContext,
  viewState: Ref<ViewState>
) {
  const mode = computed<ViewMode['mode']>({
    get: () => viewState.value.mode,
    set: (value) => {
      if (value === viewState.value.mode) {
        return;
      }

      // Update locally first so the view + active button switch immediately.
      viewState.value.mode = value;

      // Reflect the change in the URL and refresh the server-rendered bits.
      const params = new URLSearchParams(window.location.search);
      router.visit(
        index(
          {
            page: props.page ?? '',
            sectionHandle: props.sectionHandle ?? undefined,
          },
          {query: {...Object.fromEntries(params), viewMode: value}}
        ),
        {
          only: ['data', 'pagination', 'contentHtml'],
          preserveState: true,
          preserveScroll: true,
        }
      );
    },
  });

  // On a fresh full-page load the server renders for the default `table` mode
  // (it has no access to the persisted view state), so if local storage restored
  // a non-table mode, re-request the server-rendered elements for it — mirroring
  // how `useElementIndexSort` restores a persisted sort into the URL on load.
  onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const persisted = viewState.value.mode;

    if (params.has('viewMode') || persisted === 'table') {
      return;
    }

    router.visit(
      index(
        {
          page: props.page ?? '',
          sectionHandle: props.sectionHandle ?? undefined,
        },
        {query: {...Object.fromEntries(params), viewMode: persisted}}
      ),
      {
        only: ['data', 'pagination', 'contentHtml'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    );
  });

  return {mode};
}
